<?php
// api/products.php — handles: list | categories | search
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {

    // -------------------------------------------------------
    // GET /api/products.php?action=list[&category=GPU]
    // -------------------------------------------------------
    case 'list': {
        $db  = getDB();
        $cat = $_GET['category'] ?? '';

        if ($cat) {
            $stmt = $db->prepare('SELECT * FROM products WHERE category_slug = ? ORDER BY id');
            $stmt->execute([$cat]);
        } else {
            $stmt = $db->query('SELECT * FROM products ORDER BY id');
        }

        $products = $stmt->fetchAll();

        // Normalise types for JSON
        foreach ($products as &$p) {
            $p['id']           = (int)   $p['id'];
            $p['price']        = (float) $p['price'];
            $p['old_price']    = $p['old_price'] !== null ? (float) $p['old_price'] : null;
            $p['rating']       = (float) $p['rating'];
            $p['review_count'] = (int)   $p['review_count'];
            $p['stock']        = (int)   $p['stock'];
        }
        unset($p);

        json_response(['success' => true, 'products' => $products]);
        break;
    }

    // -------------------------------------------------------
    // GET /api/products.php?action=categories
    // -------------------------------------------------------
    case 'categories': {
        $db   = getDB();
        $stmt = $db->query('SELECT * FROM categories ORDER BY id');
        json_response(['success' => true, 'categories' => $stmt->fetchAll()]);
        break;
    }

    // -------------------------------------------------------
    // GET /api/products.php?action=search&q=ryzen
    // -------------------------------------------------------
    case 'search': {
        $q  = '%' . trim($_GET['q'] ?? '') . '%';
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT * FROM products
             WHERE name LIKE ? OR brand LIKE ?
             ORDER BY review_count DESC'
        );
        $stmt->execute([$q, $q]);
        $products = $stmt->fetchAll();

        foreach ($products as &$p) {
            $p['id']           = (int)   $p['id'];
            $p['price']        = (float) $p['price'];
            $p['old_price']    = $p['old_price'] !== null ? (float) $p['old_price'] : null;
            $p['rating']       = (float) $p['rating'];
            $p['review_count'] = (int)   $p['review_count'];
            $p['stock']        = (int)   $p['stock'];
        }
        unset($p);

        json_response(['success' => true, 'products' => $products]);
        break;
    }

    default:
        json_response(['success' => false, 'error' => 'Unknown action'], 400);
}
