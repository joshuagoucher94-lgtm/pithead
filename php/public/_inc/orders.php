<?php

declare(strict_types=1);

function pithead_generate_order_number(): string
{
    return 'PH-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('ymd');
}

/**
 * @param list<array{product: array<string,mixed>, qty:int}> $resolvedLines
 * @return array{order_id:int, order_number:string, total_cents:int}
 */
function pithead_create_order_draft(
    PDO $pdo,
    array $resolvedLines,
    string $email,
    string $name,
    int $shippingCents = 0
): array {
    if ($resolvedLines === []) {
        throw new RuntimeException('Empty cart');
    }
    $subtotal = 0;
    foreach ($resolvedLines as $l) {
        $subtotal += (int) $l['product']['price_cents'] * $l['qty'];
    }
    $total = $subtotal + $shippingCents;
    $currency = (string) ($resolvedLines[0]['product']['currency'] ?? 'gbp');
    $orderNumber = pithead_generate_order_number();

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare(
            'INSERT INTO orders (order_number, email, name, status, subtotal_cents, shipping_cents, total_cents, currency)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $orderNumber,
            $email,
            $name,
            'pending_payment',
            $subtotal,
            $shippingCents,
            $total,
            $currency,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $itemSt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, name_snapshot, unit_price_cents, quantity)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($resolvedLines as $l) {
            $p = $l['product'];
            $itemSt->execute([
                $orderId,
                (int) $p['id'],
                (string) $p['name'],
                (int) $p['price_cents'],
                $l['qty'],
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return [
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'total_cents' => $total,
    ];
}
