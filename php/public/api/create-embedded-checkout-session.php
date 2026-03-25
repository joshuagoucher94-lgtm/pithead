<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/_inc/stripe_init.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pithead_json_response(['error' => 'Method not allowed'], 405);
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
    pithead_json_response(['error' => 'Invalid JSON'], 400);
}

$token = (string) ($data['csrf'] ?? '');
if (!pithead_csrf_validate($token)) {
    pithead_json_response(['error' => 'Invalid CSRF'], 403);
}

$email = trim((string) ($data['email'] ?? ''));
$name = trim((string) ($data['name'] ?? ''));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    pithead_json_response(['error' => 'Valid email required'], 422);
}
if ($name === '') {
    pithead_json_response(['error' => 'Name required'], 422);
}

try {
    $pdo = pithead_pdo();
    $resolved = pithead_cart_resolve($pdo);
    if ($resolved['lines'] === []) {
        pithead_json_response(['error' => 'Cart empty'], 422);
    }

    $draft = pithead_create_order_draft($pdo, $resolved['lines'], $email, $name, 0);
    $orderId = $draft['order_id'];
    $orderNumber = $draft['order_number'];
    $total = $draft['total_cents'];
    $currency = strtolower((string) ($resolved['lines'][0]['product']['currency'] ?? 'gbp'));

    $lineItems = [];
    foreach ($resolved['lines'] as $l) {
        $p = $l['product'];
        $lineItems[] = [
            'quantity' => $l['qty'],
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => (int) $p['price_cents'],
                'product_data' => [
                    'name' => (string) $p['name'],
                    'metadata' => [
                        'product_id' => (string) $p['id'],
                        'sku' => (string) ($p['sku'] ?? ''),
                    ],
                ],
            ],
        ];
    }

    $stripe = pithead_stripe_client();
    $returnUrl = pithead_base_url() . '/shop/thank-you.php?session_id={CHECKOUT_SESSION_ID}';

    $session = $stripe->checkout->sessions->create([
        'ui_mode' => 'embedded',
        'mode' => 'payment',
        'line_items' => $lineItems,
        'return_url' => $returnUrl,
        'customer_email' => $email,
        'client_reference_id' => (string) $orderId,
        'metadata' => [
            'order_id' => (string) $orderId,
            'order_number' => $orderNumber,
        ],
        'payment_intent_data' => [
            'metadata' => [
                'order_id' => (string) $orderId,
                'order_number' => $orderNumber,
            ],
        ],
    ]);

    $sessionId = (string) $session->id;
    $clientSecret = (string) $session->client_secret;
    if ($clientSecret === '') {
        throw new RuntimeException('Missing client_secret on Checkout Session');
    }

    $up = $pdo->prepare('UPDATE orders SET stripe_checkout_session_id = ? WHERE id = ?');
    $up->execute([$sessionId, $orderId]);

    pithead_json_response([
        'clientSecret' => $clientSecret,
        'publishableKey' => pithead_stripe_config()['publishable_key'],
        'orderNumber' => $orderNumber,
    ]);
} catch (Throwable $e) {
    error_log('create-embedded-checkout-session: ' . $e->getMessage());
    pithead_json_response(['error' => 'Checkout unavailable'], 500);
}
