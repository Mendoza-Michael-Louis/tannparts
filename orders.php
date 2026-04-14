<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

function requireAuth(): int {
    if (!isset($_SESSION['user_id'])) {
        json_response(['success' => false, 'error' => 'Not authenticated'], 401);
    }
    return (int) $_SESSION['user_id'];
}

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'place': {
        $userId = requireAuth();
        $db     = getDB();

        $stmt = $db->prepare(
            'SELECT ci.cart_item_qty AS quantity, p.id AS product_id, p.name, p.price
             FROM cart_items ci
             JOIN products_full p ON p.id = ci.product_id
             WHERE ci.user_id = ?'
        );
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll();

        if (empty($items)) {
            json_response(['success' => false, 'error' => 'Cart is empty'], 422);
        }

        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['price'] * (int) $item['quantity'];
        }
        $shipping = $subtotal >= 99 ? 0.0 : 12.99;
        $total    = $subtotal + $shipping;

        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'INSERT INTO orders (user_id, order_subtotal, order_shipping, order_total, order_status)
                 VALUES (?, ?, ?, ?, "pending")'
            );
            $stmt->execute([$userId, $subtotal, $shipping, $total]);
            $orderId = (int) $db->lastInsertId();

            $itemStmt = $db->prepare(
                'INSERT INTO order_items (order_id, product_id, order_items_price, order_items_qty, order_items_total)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($items as $item) {
                $lineTotal = (float) $item['price'] * (int) $item['quantity'];
                $itemStmt->execute([$orderId, (int)$item['product_id'], (float)$item['price'], (int)$item['quantity'], $lineTotal]);
            }

            $db->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
            $db->commit();

            json_response(['success' => true, 'order_id' => $orderId, 'total' => $total, 'shipping' => $shipping], 201);
        } catch (Exception $e) {
            $db->rollBack();
            json_response(['success' => false, 'error' => 'Order failed: ' . $e->getMessage()], 500);
        }
        break;
    }

    case 'history': {
        $userId = requireAuth();
        $db     = getDB();

        $stmt = $db->prepare(
            'SELECT o.order_id AS id, o.order_subtotal AS subtotal, o.order_shipping AS shipping,
                    o.order_total AS total, o.order_status AS status, o.placed_at,
                    COUNT(oi.product_id) AS item_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.order_id
             WHERE o.user_id = ?
             GROUP BY o.order_id
             ORDER BY o.placed_at DESC'
        );
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$o) {
            $o['id']         = (int)   $o['id'];
            $o['subtotal']   = (float) $o['subtotal'];
            $o['shipping']   = (float) $o['shipping'];
            $o['total']      = (float) $o['total'];
            $o['item_count'] = (int)   $o['item_count'];
        }
        unset($o);

        json_response(['success' => true, 'orders' => $orders]);
        break;
    }

    case 'detail': {
        $userId  = requireAuth();
        $orderId = (int) ($_GET['id'] ?? 0);
        $db      = getDB();

        $stmt = $db->prepare(
            'SELECT order_id AS id, order_subtotal AS subtotal, order_shipping AS shipping,
                    order_total AS total, order_status AS status, placed_at
             FROM orders WHERE order_id = ? AND user_id = ?'
        );
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();

        if (!$order) json_response(['success' => false, 'error' => 'Order not found'], 404);

        $stmt = $db->prepare(
            'SELECT oi.product_id, p.product_name, oi.order_items_price AS unit_price,
                    oi.order_items_qty AS quantity, oi.order_items_total AS line_total
             FROM order_items oi
             JOIN products p ON p.product_id = oi.product_id
             WHERE oi.order_id = ?'
        );
        $stmt->execute([$orderId]);
        $lineItems = $stmt->fetchAll();

        foreach ($lineItems as &$li) {
            $li['product_id'] = (int)   $li['product_id'];
            $li['unit_price'] = (float) $li['unit_price'];
            $li['quantity']   = (int)   $li['quantity'];
            $li['line_total'] = (float) $li['line_total'];
        }
        unset($li);

        $order['id']       = (int)   $order['id'];
        $order['subtotal'] = (float) $order['subtotal'];
        $order['shipping'] = (float) $order['shipping'];
        $order['total']    = (float) $order['total'];
        $order['items']    = $lineItems;

        json_response(['success' => true, 'order' => $order]);
        break;
    }

    default:
        json_response(['success' => false, 'error' => 'Unknown action'], 400);
}
