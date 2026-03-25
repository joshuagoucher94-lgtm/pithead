<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/_inc/stripe_init.php';
require_once dirname(__DIR__) . '/partials/layout.php';

$sessionId = (string) ($_GET['session_id'] ?? '');
$orderNumber = '';
$status = 'pending';
$orderId = 0;

if ($sessionId !== '') {
    try {
        $stripe = pithead_stripe_client();
        $session = $stripe->checkout->sessions->retrieve($sessionId);
        $paid = ((string) ($session->payment_status ?? '')) === 'paid';
        $meta = $session->metadata ? $session->metadata->toArray() : [];
        $orderNumber = (string) ($meta['order_number'] ?? '');
        $orderId = (int) ($meta['order_id'] ?? 0);
        if ($orderId === 0 && (string) ($session->client_reference_id ?? '') !== '') {
            $orderId = (int) $session->client_reference_id;
        }
        if ($paid && $orderId > 0) {
            $pdo = pithead_pdo();
            $rawPi = $session->payment_intent ?? '';
            $pi = is_object($rawPi) && isset($rawPi->id) ? (string) $rawPi->id : (string) $rawPi;
            $st = $pdo->prepare(
                'UPDATE orders SET status = ?, stripe_payment_intent_id = COALESCE(?, stripe_payment_intent_id) WHERE id = ? AND status = ?'
            );
            $st->execute(['paid', $pi !== '' ? $pi : null, $orderId, 'pending_payment']);
            if ($st->rowCount() > 0 || $paid) {
                pithead_cart_clear();
            }
            $status = 'paid';
        }
        if ($orderNumber === '' && $orderId > 0) {
            $pdo = pithead_pdo();
            $q = $pdo->prepare('SELECT order_number FROM orders WHERE id = ?');
            $q->execute([$orderId]);
            $row = $q->fetch();
            if ($row !== false) {
                $orderNumber = (string) $row['order_number'];
            }
        }
    } catch (Throwable $e) {
        error_log('thank-you: ' . $e->getMessage());
    }
}

pithead_layout_start(['title' => 'Order — PITHEAD ROASTWORKS', 'main_class' => 'py-24']);
?>
<div class="mx-auto max-w-2xl px-4 md:px-8">
  <h1 class="text-3xl font-bold uppercase tracking-tight">Order status</h1>
  <?php if ($sessionId === '') : ?>
    <p class="mt-6 text-offwhite/70">Missing session.</p>
  <?php else : ?>
    <p class="mt-6 text-lg font-medium text-offwhite/85">
      <?php if ($orderNumber !== '') : ?>
        Reference <?= pithead_h($orderNumber) ?>.
      <?php else : ?>
        Payment received.
      <?php endif; ?>
    </p>
    <p class="mt-4 text-sm text-offwhite/60">
      <?= $status === 'paid' ? 'Confirmed. Confirmation email may follow from your mail server configuration.' : 'If payment is processing, status will update via webhook.' ?>
    </p>
  <?php endif; ?>
  <a href="/shop/" class="mt-10 inline-block border-2 border-offwhite px-6 py-3 text-xs font-bold uppercase tracking-widest hover:border-imperial">Back to shop</a>
</div>
<?php
pithead_layout_end();
