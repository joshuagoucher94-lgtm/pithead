<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = pithead_pdo();
} catch (Throwable $e) {
    pithead_json_response(['error' => 'Service unavailable'], 503);
}

$featured = isset($_GET['featured']) && $_GET['featured'] === '1';

if ($featured) {
    $st = $pdo->query(
        'SELECT id, slug, name, tagline, short_description, price_cents, currency, weight_label, sku
         FROM products WHERE is_active = 1 AND is_featured = 1 ORDER BY id ASC LIMIT 12'
    );
} else {
    $st = $pdo->query(
        'SELECT id, slug, name, tagline, short_description, price_cents, currency, weight_label, sku
         FROM products WHERE is_active = 1 ORDER BY id ASC'
    );
}

$products = $st->fetchAll();

pithead_json_response([
    'products' => $products,
    'csrf' => pithead_csrf_token(),
]);
