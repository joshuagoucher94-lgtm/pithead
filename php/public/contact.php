<?php

declare(strict_types=1);

require __DIR__ . '/_inc/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';

$errors = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pithead_csrf_validate((string) ($_POST['csrf'] ?? ''))) {
        $errors[] = 'Invalid session.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            $errors[] = 'Name, valid email, and message required.';
        } else {
            try {
                $pdo = pithead_pdo();
                $st = $pdo->prepare(
                    'INSERT INTO contact_messages (name, email, subject, message) VALUES (?,?,?,?)'
                );
                $st->execute([$name, $email, $subject !== '' ? $subject : null, $message]);
                $ok = true;
            } catch (Throwable $e) {
                $errors[] = 'Could not save message.';
            }
        }
    }
}

$csrf = pithead_csrf_token();
pithead_layout_start(['title' => 'Contact — PITHEAD ROASTWORKS', 'main_class' => 'py-16 md:py-24']);
?>
<div class="mx-auto max-w-2xl px-4 md:px-8">
  <h1 class="text-4xl font-bold uppercase tracking-tight">Contact</h1>
  <?php if ($ok) : ?>
    <p class="mt-8 text-offwhite/80">Sent.</p>
  <?php else : ?>
    <?php foreach ($errors as $e) : ?>
      <p class="mt-4 text-sm text-imperial"><?= pithead_h($e) ?></p>
    <?php endforeach; ?>
    <form method="post" class="mt-10 space-y-6">
      <input type="hidden" name="csrf" value="<?= pithead_h($csrf) ?>" />
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/80">
        Name
        <input name="name" required class="mt-2 w-full border border-offwhite/30 bg-coal px-4 py-3 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/80">
        Email
        <input name="email" type="email" required class="mt-2 w-full border border-offwhite/30 bg-coal px-4 py-3 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/80">
        Subject
        <input name="subject" class="mt-2 w-full border border-offwhite/30 bg-coal px-4 py-3 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/80">
        Message
        <textarea name="message" required rows="6" class="mt-2 w-full border border-offwhite/30 bg-coal px-4 py-3 text-sm"></textarea>
      </label>
      <button type="submit" class="border-2 border-imperial bg-imperial px-8 py-4 text-xs font-bold uppercase tracking-widest text-offwhite hover:bg-coal">
        Send
      </button>
    </form>
  <?php endif; ?>
</div>
<?php
pithead_layout_end();
