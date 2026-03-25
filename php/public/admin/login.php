<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/_inc/auth.php';

if (pithead_admin() !== null) {
    pithead_redirect('/admin/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pithead_csrf_validate((string) ($_POST['csrf'] ?? ''))) {
        $error = 'Invalid session.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        try {
            $pdo = pithead_pdo();
            if (pithead_admin_login($pdo, $email, $password)) {
                session_regenerate_id(true);
                pithead_redirect('/admin/');
            }
            $error = 'Invalid credentials.';
        } catch (Throwable $e) {
            $error = 'Unavailable.';
        }
    }
}

$csrf = pithead_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/assets/app.css" />
  <title>Admin — PITHEAD</title>
</head>
<body class="min-h-screen bg-coal text-offwhite font-sans">
  <div class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-4 py-16">
    <h1 class="text-2xl font-bold uppercase tracking-tight">Admin</h1>
    <?php if ($error !== '') : ?>
      <p class="mt-4 text-sm text-imperial"><?= pithead_h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="mt-8 space-y-4">
      <input type="hidden" name="csrf" value="<?= pithead_h($csrf) ?>" />
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">
        Email
        <input type="email" name="email" required class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">
        Password
        <input type="password" name="password" required class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm" />
      </label>
      <button type="submit" class="w-full border-2 border-imperial bg-imperial py-3 text-xs font-bold uppercase tracking-widest hover:bg-coal">Sign in</button>
    </form>
  </div>
</body>
</html>
