<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/_inc/auth.php';

pithead_require_admin();
$pdo = pithead_pdo();

$keys = ['instagram_url', 'location_text', 'order_notification_email'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pithead_csrf_validate((string) ($_POST['csrf'] ?? ''))) {
    foreach ($keys as $k) {
        if (!array_key_exists($k, $_POST)) {
            continue;
        }
        $v = trim((string) $_POST[$k]);
        $st = $pdo->prepare('INSERT INTO site_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
        $st->execute([$k, $v]);
    }
    $msg = 'Saved.';
}

$values = [];
foreach ($keys as $k) {
    $values[$k] = pithead_setting($pdo, $k, '');
}
$csrf = pithead_csrf_token();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/assets/app.css" />
  <title>Settings — Admin</title>
</head>
<body class="min-h-screen bg-coal text-offwhite">
<?php require __DIR__ . '/_nav.php'; ?>
  <main class="mx-auto max-w-2xl px-4 py-10">
    <h1 class="text-2xl font-bold uppercase tracking-tight">Site settings</h1>
    <?php if ($msg !== '') : ?>
      <p class="mt-4 text-sm text-stone"><?= pithead_h($msg) ?></p>
    <?php endif; ?>
    <form method="post" class="mt-8 space-y-6">
      <input type="hidden" name="csrf" value="<?= pithead_h($csrf) ?>" />
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Instagram URL
        <input name="instagram_url" value="<?= pithead_h((string) $values['instagram_url']) ?>" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Location text
        <textarea name="location_text" rows="3" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm"><?= pithead_h((string) $values['location_text']) ?></textarea>
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Order notification email
        <input name="order_notification_email" type="email" value="<?= pithead_h((string) $values['order_notification_email']) ?>" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm" />
      </label>
      <button type="submit" class="border-2 border-imperial bg-imperial px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-coal">Save</button>
    </form>
  </main>
</body>
</html>
