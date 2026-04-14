<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

function normalise(array &$products): void {
    foreach ($products as &$p) {
        $p['id']           = (int)   $p['id'];
        $p['brand_id']     = (int)   $p['brand_id'];
        $p['cat_id']       = (int)   $p['cat_id'];
        $p['price']        = (float) $p['price'];
        $p['old_price']    = isset($p['old_price'])&&$p['old_price']!==null ? (float)$p['old_price'] : null;
        $p['rating']       = (float) $p['rating'];
        $p['review_count'] = (int)   $p['review_count'];
        $p['stock']        = (int)   $p['stock'];
    }
    unset($p);
}

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list': {
        $db  = getDB();
        $cat = $_GET['category'] ?? '';
        if ($cat) {
            $stmt = $db->prepare('SELECT * FROM products_full WHERE category_slug = ? ORDER BY id');
            $stmt->execute([$cat]);
        } else {
            $stmt = $db->query('SELECT * FROM products_full ORDER BY id');
        }
        $products = $stmt->fetchAll();
        normalise($products);
        json_response(['success'=>true,'products'=>$products]);
        break;
    }
    case 'categories': {
        $db   = getDB();
        $stmt = $db->query('SELECT category_id AS id, category_name AS name, category_slug AS slug FROM categories ORDER BY category_id');
        json_response(['success'=>true,'categories'=>$stmt->fetchAll()]);
        break;
    }
    case 'brands': {
        $db   = getDB();
        $stmt = $db->query('SELECT brand_id AS id, brand_name AS name FROM brands ORDER BY brand_id');
        json_response(['success'=>true,'brands'=>$stmt->fetchAll()]);
        break;
    }
    case 'search': {
        $q    = '%'.trim($_GET['q']??'').'%';
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM products_full WHERE name LIKE ? OR brand LIKE ? ORDER BY review_count DESC');
        $stmt->execute([$q,$q]);
        $products = $stmt->fetchAll();
        normalise($products);
        json_response(['success'=>true,'products'=>$products]);
        break;
    }
    default:
        json_response(['success'=>false,'error'=>'Unknown action'],400);
}
