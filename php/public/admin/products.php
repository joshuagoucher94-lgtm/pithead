<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/_inc/auth.php';

pithead_require_admin();
$pdo = pithead_pdo();
$rows = $pdo->query('SELECT id, slug, name, price_cents, is_active, is_featured FROM products ORDER BY id DESC')->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/assets/app.css" />
  <title>Products — Admin</title>
</head>
<body class="min-h-screen bg-coal text-offwhite">
<?php require __DIR__ . '/_nav.php'; ?>
  <main class="mx-auto max-w-6xl px-4 py-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <h1 class="text-2xl font-bold uppercase tracking-tight">Products</h1>
      <a href="/admin/product-edit.php" class="border-2 border-imperial px-4 py-2 text-xs font-bold uppercase tracking-widest hover:bg-imperial">New</a>
    </div>
    <div class="mt-8 overflow-x-auto border border-offwhite/15">
      <table class="w-full min-w-[640px] text-left text-sm">
        <thead class="border-b border-offwhite/15 text-xs font-bold uppercase tracking-widest text-offwhite/50">
          <tr>
            <th class="p-3">Name</th>
            <th class="p-3">Slug</th>
            <th class="p-3">Price</th>
            <th class="p-3">Active</th>
            <th class="p-3">Featured</th>
            <th class="p-3"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r) :
              $price = number_format(((int) $r['price_cents']) / 100, 2);
              ?>
            <tr class="border-b border-offwhite/10">
              <td class="p-3 font-semibold"><?= pithead_h((string) $r['name']) ?></td>
              <td class="p-3 text-offwhite/70"><?= pithead_h((string) $r['slug']) ?></td>
              <td class="p-3 tabular-nums">£<?= pithead_h($price) ?></td>
              <td class="p-3"><?= (int) $r['is_active'] ? 'Yes' : 'No' ?></td>
              <td class="p-3"><?= (int) $r['is_featured'] ? 'Yes' : 'No' ?></td>
              <td class="p-3">
                <a href="/admin/product-edit.php?id=<?= (int) $r['id'] ?>" class="text-imperial hover:underline">Edit</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>
