<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/_inc/auth.php';

pithead_require_admin();
$pdo = pithead_pdo();
$id = (int) ($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$st->execute([$id]);
$order = $st->fetch();
if ($order === false) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$it = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$it->execute([$id]);
$items = $it->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/assets/app.css" />
  <title>Order <?= pithead_h((string) $order['order_number']) ?> — Admin</title>
</head>
<body class="min-h-screen bg-coal text-offwhite">
<?php require __DIR__ . '/_nav.php'; ?>
  <main class="mx-auto max-w-3xl px-4 py-10">
    <a href="/admin/orders.php" class="text-xs font-bold uppercase tracking-widest text-stone hover:text-offwhite">← Orders</a>
    <h1 class="mt-4 text-2xl font-bold uppercase tracking-tight"><?= pithead_h((string) $order['order_number']) ?></h1>
    <dl class="mt-6 space-y-2 text-sm">
      <div class="flex justify-between border-b border-offwhite/10 py-2"><dt class="text-offwhite/50">Status</dt><dd class="uppercase"><?= pithead_h((string) $order['status']) ?></dd></div>
      <div class="flex justify-between border-b border-offwhite/10 py-2"><dt class="text-offwhite/50">Email</dt><dd><?= pithead_h((string) $order['email']) ?></dd></div>
      <div class="flex justify-between border-b border-offwhite/10 py-2"><dt class="text-offwhite/50">Name</dt><dd><?= pithead_h((string) $order['name']) ?></dd></div>
      <div class="flex justify-between border-b border-offwhite/10 py-2"><dt class="text-offwhite/50">Stripe session</dt><dd class="break-all font-mono text-xs"><?= pithead_h((string) ($order['stripe_checkout_session_id'] ?? '')) ?></dd></div>
      <div class="flex justify-between border-b border-offwhite/10 py-2"><dt class="text-offwhite/50">Payment intent</dt><dd class="break-all font-mono text-xs"><?= pithead_h((string) ($order['stripe_payment_intent_id'] ?? '')) ?></dd></div>
    </dl>
    <h2 class="mt-10 text-sm font-bold uppercase tracking-widest text-imperial">Lines</h2>
    <ul class="mt-4 space-y-3 text-sm">
      <?php foreach ($items as $li) :
          $line = number_format(((int) $li['unit_price_cents']) * (int) $li['quantity'] / 100, 2);
          ?>
        <li class="flex justify-between border-b border-offwhite/10 py-2">
          <span><?= pithead_h((string) $li['name_snapshot']) ?> × <?= (int) $li['quantity'] ?></span>
          <span class="tabular-nums">£<?= pithead_h($line) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="mt-6 text-lg font-bold tabular-nums">Total £<?= pithead_h(number_format(((int) $order['total_cents']) / 100, 2)) ?></p>
  </main>
</body>
</html>
