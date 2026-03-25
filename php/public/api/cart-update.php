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

$lines = [];
foreach (($_POST['qty'] ?? []) as $pid => $q) {
    $lines[] = [
        'product_id' => (int) $pid,
        'qty' => max(0, (int) $q),
    ];
}
$lines = array_values(array_filter($lines, static fn ($l) => $l['qty'] > 0));
pithead_cart_set($lines);
pithead_redirect('/shop/cart.php');
