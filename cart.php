<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

function requireAuth(): int {
    if (!isset($_SESSION['user_id'])) json_response(['success'=>false,'error'=>'Not authenticated'],401);
    return (int) $_SESSION['user_id'];
}

function fetchCart(PDO $db, int $userId): array {
    $stmt = $db->prepare(
        'SELECT ci.cart_id AS id, ci.product_id, ci.cart_item_qty AS quantity,
                p.name, p.brand, p.price, p.old_price, p.stock
         FROM cart_items ci
         JOIN products_full p ON p.id = ci.product_id
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
        $r['old_price']  = $r['old_price'] !== null ? (float)$r['old_price'] : null;
        $r['stock']      = (int)   $r['stock'];
    }
    unset($r);
    return $rows;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get': {
        $userId = requireAuth();
        json_response(['success'=>true,'cart'=>fetchCart(getDB(),$userId)]);
        break;
    }
    case 'add': {
        $userId = requireAuth();
        $data   = get_input();
        $pid    = (int)($data['product_id']??0);
        $qty    = max(1,(int)($data['quantity']??1));
        if (!$pid) json_response(['success'=>false,'error'=>'product_id required'],422);
        $db   = getDB();
        $stmt = $db->prepare('SELECT product_id FROM products WHERE product_id = ?');
        $stmt->execute([$pid]);
        if (!$stmt->fetch()) json_response(['success'=>false,'error'=>'Product not found'],404);
        $stmt = $db->prepare('INSERT INTO cart_items (user_id,product_id,cart_item_qty) VALUES (?,?,?) ON DUPLICATE KEY UPDATE cart_item_qty=cart_item_qty+VALUES(cart_item_qty)');
        $stmt->execute([$userId,$pid,$qty]);
        json_response(['success'=>true,'cart'=>fetchCart($db,$userId)]);
        break;
    }
    case 'update': {
        $userId = requireAuth();
        $data   = get_input();
        $pid    = (int)($data['product_id']??0);
        $qty    = (int)($data['quantity']??0);
        if (!$pid) json_response(['success'=>false,'error'=>'product_id required'],422);
        $db = getDB();
        if ($qty<=0) {
            $db->prepare('DELETE FROM cart_items WHERE user_id=? AND product_id=?')->execute([$userId,$pid]);
        } else {
            $db->prepare('UPDATE cart_items SET cart_item_qty=? WHERE user_id=? AND product_id=?')->execute([$qty,$userId,$pid]);
        }
        json_response(['success'=>true,'cart'=>fetchCart($db,$userId)]);
        break;
    }
    case 'remove': {
        $userId = requireAuth();
        $data   = get_input();
        $pid    = (int)($data['product_id']??0);
        if (!$pid) json_response(['success'=>false,'error'=>'product_id required'],422);
        $db = getDB();
        $db->prepare('DELETE FROM cart_items WHERE user_id=? AND product_id=?')->execute([$userId,$pid]);
        json_response(['success'=>true,'cart'=>fetchCart($db,$userId)]);
        break;
    }
    case 'clear': {
        $userId = requireAuth();
        $db = getDB();
        $db->prepare('DELETE FROM cart_items WHERE user_id=?')->execute([$userId]);
        json_response(['success'=>true,'cart'=>[]]);
        break;
    }
    default:
        json_response(['success'=>false,'error'=>'Unknown action'],400);
}
