<?php
// api/orders.php — handles: place | history | detail
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

function requireAuth(): int {
    if (!isset($_SESSION['user_id'])) {
        json_response(['success' => false, 'error' => 'Not authenticated'], 401);
    }
    return (int) $_SESSION['user_id'];
}

$action = $_GET['action'] ?? '';

switch ($action) {

    // -------------------------------------------------------
    // POST /api/orders.php?action=place
    // Converts the user's current cart into an order
    // -------------------------------------------------------
    case 'place': {
        $userId = requireAuth();
        $db     = getDB();

        // Load cart
        $stmt = $db->prepare(
            'SELECT ci.quantity, p.id AS product_id, p.name, p.icon, p.price, p.stock
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             WHERE ci.user_id = ?'
        );
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll();

        if (empty($items)) {
            json_response(['success' => false, 'error' => 'Cart is empty'], 422);
        }

        // Compute totals
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['price'] * (int) $item['quantity'];
        }
        $shipping = $subtotal >= 99 ? 0.0 : 12.99;
        $total    = $subtotal + $shipping;

        $db->beginTransaction();
        try {
            // Insert order
            $stmt = $db->prepare(
                'INSERT INTO orders (user_id, subtotal, shipping, total, status)
                 VALUES (?, ?, ?, ?, "pending")'
            );
            $stmt->execute([$userId, $subtotal, $shipping, $total]);
            $orderId = (int) $db->lastInsertId();

            // Insert order items
            $itemStmt = $db->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, product_icon, unit_price, quantity)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($items as $item) {
                $itemStmt->execute([
                    $orderId,
                    (int)   $item['product_id'],
                    $item['name'],
                    $item['icon'],
                    (float) $item['price'],
                    (int)   $item['quantity'],
                ]);
            }

            // Clear cart
            $db->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);

            $db->commit();

            json_response([
                'success'  => true,
                'order_id' => $orderId,
                'total'    => $total,
                'shipping' => $shipping,
            ], 201);

        } catch (Exception $e) {
            $db->rollBack();
            json_response(['success' => false, 'error' => 'Order failed: ' . $e->getMessage()], 500);
        }
        break;
    }

    // -------------------------------------------------------
    // GET /api/orders.php?action=history
    // Returns all orders for the logged-in user (newest first)
    // -------------------------------------------------------
    case 'history': {
        $userId = requireAuth();
        $db     = getDB();

        $stmt = $db->prepare(
            'SELECT o.id, o.subtotal, o.shipping, o.total, o.status, o.placed_at,
                    COUNT(oi.id) AS item_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = ?
             GROUP BY o.id
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

    // -------------------------------------------------------
    // GET /api/orders.php?action=detail&id=42
    // Returns one order with its line items
    // -------------------------------------------------------
    case 'detail': {
        $userId  = requireAuth();
        $orderId = (int) ($_GET['id'] ?? 0);
        $db      = getDB();

        $stmt = $db->prepare(
            'SELECT id, subtotal, shipping, total, status, placed_at
             FROM orders WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();

        if (!$order) {
            json_response(['success' => false, 'error' => 'Order not found'], 404);
        }

        $stmt = $db->prepare(
            'SELECT product_id, product_name, product_icon, unit_price, quantity
             FROM order_items WHERE order_id = ?'
        );
        $stmt->execute([$orderId]);
        $lineItems = $stmt->fetchAll();

        foreach ($lineItems as &$li) {
            $li['product_id'] = (int)   $li['product_id'];
            $li['unit_price'] = (float) $li['unit_price'];
            $li['quantity']   = (int)   $li['quantity'];
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
