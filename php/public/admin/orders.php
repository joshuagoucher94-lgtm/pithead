<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/_inc/auth.php';

pithead_require_admin();
$pdo = pithead_pdo();
$rows = $pdo->query(
    'SELECT id, order_number, email, status, total_cents, currency, created_at FROM orders ORDER BY id DESC LIMIT 200'
)->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/assets/app.css" />
  <title>Orders — Admin</title>
</head>
<body class="min-h-screen bg-coal text-offwhite">
<?php require __DIR__ . '/_nav.php'; ?>
  <main class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="text-2xl font-bold uppercase tracking-tight">Orders</h1>
    <div class="mt-8 overflow-x-auto border border-offwhite/15">
      <table class="w-full min-w-[720px] text-left text-sm">
        <thead class="border-b border-offwhite/15 text-xs font-bold uppercase tracking-widest text-offwhite/50">
          <tr>
            <th class="p-3">#</th>
            <th class="p-3">Ref</th>
            <th class="p-3">Email</th>
            <th class="p-3">Status</th>
            <th class="p-3">Total</th>
            <th class="p-3">When</th>
            <th class="p-3"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r) :
              $tot = number_format(((int) $r['total_cents']) / 100, 2);
              ?>
            <tr class="border-b border-offwhite/10">
              <td class="p-3 tabular-nums"><?= (int) $r['id'] ?></td>
              <td class="p-3 font-mono text-xs"><?= pithead_h((string) $r['order_number']) ?></td>
              <td class="p-3"><?= pithead_h((string) $r['email']) ?></td>
              <td class="p-3 uppercase"><?= pithead_h((string) $r['status']) ?></td>
              <td class="p-3 tabular-nums">£<?= pithead_h($tot) ?></td>
              <td class="p-3 text-offwhite/60"><?= pithead_h((string) $r['created_at']) ?></td>
              <td class="p-3"><a href="/admin/order-view.php?id=<?= (int) $r['id'] ?>" class="text-imperial">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>
