<?php
// api/cart.php — handles: get | add | update | remove | clear
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

// All cart operations require a logged-in user
function requireAuth(): int {
    if (!isset($_SESSION['user_id'])) {
        json_response(['success' => false, 'error' => 'Not authenticated'], 401);
    }
    return (int) $_SESSION['user_id'];
}

// Returns the user's full cart with product details
function fetchCart(PDO $db, int $userId): array {
    $stmt = $db->prepare(
        'SELECT ci.id, ci.product_id, ci.quantity,
                p.name, p.brand, p.icon, p.price, p.old_price, p.stock
         FROM cart_items ci
         JOIN products p ON p.id = ci.product_id
         WHERE ci.user_id = ?
         ORDER BY ci.added_at DESC'
    );
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['id']         = (int)   $r['id'];
        $r['product_id'] = (int)   $r['product_id'];
        $r['quantity']   = (int)   $r['quantity'];
        $r['price']      = (float) $r['price'];
        $r['old_price']  = $r['old_price'] !== null ? (float) $r['old_price'] : null;
        $r['stock']      = (int)   $r['stock'];
    }
    unset($r);
    return $rows;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    // -------------------------------------------------------
    // GET /api/cart.php?action=get
    // -------------------------------------------------------
    case 'get': {
        $userId = requireAuth();
        $db     = getDB();
        json_response(['success' => true, 'cart' => fetchCart($db, $userId)]);
        break;
    }

    // -------------------------------------------------------
    // POST /api/cart.php?action=add
    // Body: { product_id, quantity? }
    // -------------------------------------------------------
    case 'add': {
        $userId = requireAuth();
        $data   = get_input();
        $pid    = (int) ($data['product_id'] ?? 0);
        $qty    = max(1, (int) ($data['quantity'] ?? 1));

        if (!$pid) {
            json_response(['success' => false, 'error' => 'product_id required'], 422);
        }

        $db = getDB();

        // Verify product exists and has stock
        $stmt = $db->prepare('SELECT stock FROM products WHERE id = ?');
        $stmt->execute([$pid]);
        $product = $stmt->fetch();
        if (!$product) {
            json_response(['success' => false, 'error' => 'Product not found'], 404);
        }

        // Upsert: if already in cart, increment quantity
        $stmt = $db->prepare(
            'INSERT INTO cart_items (user_id, product_id, quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
        );
        $stmt->execute([$userId, $pid, $qty]);

        json_response(['success' => true, 'cart' => fetchCart($db, $userId)]);
        break;
    }

    // -------------------------------------------------------
    // POST /api/cart.php?action=update
    // Body: { product_id, quantity }  — set absolute quantity; 0 removes
    // -------------------------------------------------------
    case 'update': {
        $userId = requireAuth();
        $data   = get_input();
        $pid    = (int) ($data['product_id'] ?? 0);
        $qty    = (int) ($data['quantity']   ?? 0);

        if (!$pid) {
            json_response(['success' => false, 'error' => 'product_id required'], 422);
        }

        $db = getDB();

        if ($qty <= 0) {
            $stmt = $db->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
            $stmt->execute([$userId, $pid]);
        } else {
            $stmt = $db->prepare(
                'UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?'
            );
            $stmt->execute([$qty, $userId, $pid]);
        }

        json_response(['success' => true, 'cart' => fetchCart($db, $userId)]);
        break;
    }

    // -------------------------------------------------------
    // POST /api/cart.php?action=remove
    // Body: { product_id }
    // -------------------------------------------------------
    case 'remove': {
        $userId = requireAuth();
        $data   = get_input();
        $pid    = (int) ($data['product_id'] ?? 0);

        if (!$pid) {
            json_response(['success' => false, 'error' => 'product_id required'], 422);
        }

        $db   = getDB();
        $stmt = $db->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$userId, $pid]);

        json_response(['success' => true, 'cart' => fetchCart($db, $userId)]);
        break;
    }

    // -------------------------------------------------------
    // POST /api/cart.php?action=clear
    // -------------------------------------------------------
    case 'clear': {
        $userId = requireAuth();
        $db     = getDB();
        $stmt   = $db->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $stmt->execute([$userId]);
        json_response(['success' => true, 'cart' => []]);
        break;
    }

    default:
        json_response(['success' => false, 'error' => 'Unknown action'], 400);
}
