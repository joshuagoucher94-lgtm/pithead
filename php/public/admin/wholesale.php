<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/_inc/auth.php';

pithead_require_admin();
$pdo = pithead_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pithead_csrf_validate((string) ($_POST['csrf'] ?? ''))) {
    $mid = (int) ($_POST['mark_read'] ?? 0);
    if ($mid > 0) {
        $pdo->prepare('UPDATE wholesale_enquiries SET status = ? WHERE id = ?')->execute(['read', $mid]);
    }
}

$rows = $pdo->query('SELECT * FROM wholesale_enquiries ORDER BY id DESC LIMIT 200')->fetchAll();
$csrf = pithead_csrf_token();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/assets/app.css" />
  <title>Wholesale — Admin</title>
</head>
<body class="min-h-screen bg-coal text-offwhite">
<?php require __DIR__ . '/_nav.php'; ?>
  <main class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="text-2xl font-bold uppercase tracking-tight">Wholesale enquiries</h1>
    <div class="mt-8 space-y-8">
      <?php foreach ($rows as $r) : ?>
        <article class="border border-offwhite/15 p-6">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p class="text-xs font-bold uppercase tracking-widest text-stone"><?= pithead_h((string) $r['created_at']) ?> · <?= pithead_h((string) $r['status']) ?></p>
              <p class="mt-2 text-lg font-bold"><?= pithead_h((string) $r['business_name']) ?></p>
              <p class="text-sm text-offwhite/80"><?= pithead_h((string) $r['contact_name']) ?> · <?= pithead_h((string) $r['email']) ?></p>
              <?php if (($r['phone'] ?? '') !== '') : ?>
                <p class="text-sm text-offwhite/60"><?= pithead_h((string) $r['phone']) ?></p>
              <?php endif; ?>
            </div>
            <?php if ($r['status'] === 'new') : ?>
              <form method="post">
                <input type="hidden" name="csrf" value="<?= pithead_h($csrf) ?>" />
                <input type="hidden" name="mark_read" value="<?= (int) $r['id'] ?>" />
                <button type="submit" class="border border-offwhite/40 px-3 py-2 text-xs font-bold uppercase tracking-widest hover:border-imperial">Mark read</button>
              </form>
            <?php endif; ?>
          </div>
          <p class="mt-4 whitespace-pre-wrap text-sm text-offwhite/85"><?= pithead_h((string) $r['message']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </main>
</body>
</html>
