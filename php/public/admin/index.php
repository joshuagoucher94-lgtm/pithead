<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/_inc/auth.php';

pithead_require_admin();
$pdo = pithead_pdo();

$o = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = \'paid\'')->fetchColumn();
$p = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$w = (int) $pdo->query('SELECT COUNT(*) FROM wholesale_enquiries WHERE status = \'new\'')->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/assets/app.css" />
  <title>Dashboard — Admin</title>
</head>
<body class="min-h-screen bg-coal text-offwhite">
<?php require __DIR__ . '/_nav.php'; ?>
  <main class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="text-2xl font-bold uppercase tracking-tight">Dashboard</h1>
    <div class="mt-8 grid gap-4 sm:grid-cols-3">
      <div class="border border-offwhite/15 p-6">
        <p class="text-xs font-bold uppercase tracking-widest text-stone">Paid orders</p>
        <p class="mt-2 text-3xl font-bold tabular-nums"><?= $o ?></p>
      </div>
      <div class="border border-offwhite/15 p-6">
        <p class="text-xs font-bold uppercase tracking-widest text-stone">Products</p>
        <p class="mt-2 text-3xl font-bold tabular-nums"><?= $p ?></p>
      </div>
      <div class="border border-offwhite/15 p-6">
        <p class="text-xs font-bold uppercase tracking-widest text-stone">New wholesale</p>
        <p class="mt-2 text-3xl font-bold tabular-nums"><?= $w ?></p>
      </div>
    </div>
  </main>
</body>
</html>
