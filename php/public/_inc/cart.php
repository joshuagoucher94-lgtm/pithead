<?php

declare(strict_types=1);

/**
 * @return array<int, array{product_id:int, qty:int}>
 */
function pithead_cart_get(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) {
        return [];
    }
    $out = [];
    foreach ($cart as $line) {
        if (!is_array($line)) {
            continue;
        }
        $pid = (int) ($line['product_id'] ?? 0);
        $qty = (int) ($line['qty'] ?? 0);
        if ($pid > 0 && $qty > 0) {
            $out[] = ['product_id' => $pid, 'qty' => $qty];
        }
    }
    return $out;
}

/**
 * @param array<int, array{product_id:int, qty:int}> $lines
 */
function pithead_cart_set(array $lines): void
{
    $_SESSION['cart'] = $lines;
}

function pithead_cart_add(int $productId, int $qty = 1): void
{
    $qty = max(1, $qty);
    $cart = pithead_cart_get();
    $found = false;
    foreach ($cart as $i => $line) {
        if ($line['product_id'] === $productId) {
            $cart[$i]['qty'] += $qty;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $cart[] = ['product_id' => $productId, 'qty' => $qty];
    }
    pithead_cart_set($cart);
}

function pithead_cart_clear(): void
{
    unset($_SESSION['cart']);
}

/**
 * @return array{lines: list<array{product: array<string,mixed>, qty:int}>, subtotal_cents: int}
 */
function pithead_cart_resolve(PDO $pdo): array
{
    $cart = pithead_cart_get();
    $lines = [];
    $subtotal = 0;
    foreach ($cart as $line) {
        $st = $pdo->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
        $st->execute([$line['product_id']]);
        $p = $st->fetch();
        if ($p === false) {
            continue;
        }
        $price = (int) $p['price_cents'];
        $subtotal += $price * $line['qty'];
        $lines[] = ['product' => $p, 'qty' => $line['qty']];
    }
    return ['lines' => $lines, 'subtotal_cents' => $subtotal];
}
