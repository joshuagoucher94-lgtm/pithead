<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/_inc/stripe_init.php';

pithead_stripe_autoload();

$payload = file_get_contents('php://input') ?: '';
$sig = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
$whSecret = pithead_stripe_config()['webhook_secret'];

if ($whSecret === '') {
    http_response_code(500);
    echo 'Webhook not configured';
    exit;
}

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig, $whSecret);
} catch (Throwable $e) {
    http_response_code(400);
    echo 'Invalid signature';
    exit;
}

$pdo = pithead_pdo();

$eventId = (string) $event->id;
$type = (string) $event->type;

try {
    $ins = $pdo->prepare(
        'INSERT INTO stripe_events (stripe_event_id, type, payload, processed_at) VALUES (?, ?, ?, NOW())'
    );
    try {
        $ins->execute([$eventId, $type, $payload]);
    } catch (PDOException $dup) {
        if ((int) ($dup->errorInfo[1] ?? 0) === 1062) {
            http_response_code(200);
            echo 'ok';
            exit;
        }
        throw $dup;
    }

    if ($type === 'checkout.session.completed') {
        $obj = $event->data->object;
        $arr = method_exists($obj, 'toArray') ? $obj->toArray() : [];
        $sessionId = (string) ($arr['id'] ?? '');
        $paymentStatus = (string) ($arr['payment_status'] ?? '');
        $meta = is_array($arr['metadata'] ?? null) ? $arr['metadata'] : [];
        $orderId = (int) ($meta['order_id'] ?? 0);
        if ($orderId === 0 && $sessionId !== '') {
            $q = $pdo->prepare('SELECT id FROM orders WHERE stripe_checkout_session_id = ? LIMIT 1');
            $q->execute([$sessionId]);
            $row = $q->fetch();
            if ($row !== false) {
                $orderId = (int) $row['id'];
            }
        }
        if ($orderId > 0 && ($paymentStatus === 'paid' || $paymentStatus === 'no_payment_required')) {
            $rawPi = $arr['payment_intent'] ?? '';
            $pi = is_string($rawPi) ? $rawPi : (is_array($rawPi) && isset($rawPi['id']) ? (string) $rawPi['id'] : '');
            $st = $pdo->prepare(
                'UPDATE orders SET status = ?, stripe_payment_intent_id = COALESCE(?, stripe_payment_intent_id) WHERE id = ?'
            );
            $st->execute(['paid', $pi !== '' ? $pi : null, $orderId]);
            pithead_try_send_order_confirmation_email($pdo, $orderId);
        }
    }

    if ($type === 'checkout.session.async_payment_failed' || $type === 'checkout.session.expired') {
        $obj = $event->data->object;
        $arr = method_exists($obj, 'toArray') ? $obj->toArray() : [];
        $meta = is_array($arr['metadata'] ?? null) ? $arr['metadata'] : [];
        $orderId = (int) ($meta['order_id'] ?? 0);
        $sessionId = (string) ($arr['id'] ?? '');
        if ($orderId === 0 && $sessionId !== '') {
            $q = $pdo->prepare('SELECT id FROM orders WHERE stripe_checkout_session_id = ? LIMIT 1');
            $q->execute([$sessionId]);
            $row = $q->fetch();
            if ($row !== false) {
                $orderId = (int) $row['id'];
            }
        }
        if ($orderId > 0) {
            $st = $pdo->prepare("UPDATE orders SET status = 'failed' WHERE id = ? AND status = 'pending_payment'");
            $st->execute([$orderId]);
        }
    }
} catch (Throwable $e) {
    error_log('stripe-webhook: ' . $e->getMessage());
    $err = $pdo->prepare('UPDATE stripe_events SET processing_error = ? WHERE stripe_event_id = ?');
    $err->execute([$e->getMessage(), $eventId]);
    http_response_code(500);
    exit;
}

http_response_code(200);
echo 'ok';
