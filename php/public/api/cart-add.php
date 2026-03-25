<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pithead_redirect('/shop/cart.php');
}

$token = (string) ($_POST['csrf'] ?? '');
if (!pithead_csrf_validate($token)) {
    pithead_redirect('/shop/cart.php?err=csrf');
}

$productId = (int) ($_POST['product_id'] ?? 0);
$qty = max(1, (int) ($_POST['qty'] ?? 1));
$redirect = (string) ($_POST['redirect'] ?? '/shop/cart.php');
if ($redirect === '' || !str_starts_with($redirect, '/')) {
    $redirect = '/shop/cart.php';
}

try {
    $pdo = pithead_pdo();
    $st = $pdo->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1');
    $st->execute([$productId]);
    if ($st->fetch() === false) {
        pithead_redirect('/shop/?err=product');
    }
} catch (Throwable $e) {
    pithead_redirect('/shop/?err=db');
}

pithead_cart_add($productId, $qty);
pithead_redirect($redirect);
