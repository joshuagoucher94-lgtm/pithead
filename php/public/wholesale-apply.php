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
        $business = trim((string) ($_POST['business_name'] ?? ''));
        $contact = trim((string) ($_POST['contact_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        if ($business === '' || $contact === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            $errors[] = 'All required fields must be valid.';
        } else {
            try {
                $pdo = pithead_pdo();
                $st = $pdo->prepare(
                    'INSERT INTO wholesale_enquiries (business_name, contact_name, email, phone, message) VALUES (?,?,?,?,?)'
                );
                $st->execute([
                    $business,
                    $contact,
                    $email,
                    $phone !== '' ? $phone : null,
                    $message,
                ]);
                $ok = true;
            } catch (Throwable $e) {
                $errors[] = 'Could not save enquiry.';
            }
        }
    }
}

$csrf = pithead_csrf_token();
pithead_layout_start(['title' => 'Wholesale application — PITHEAD', 'main_class' => 'py-16 md:py-24']);
?>
<div class="mx-auto max-w-2xl px-4 md:px-8">
  <h1 class="text-4xl font-bold uppercase tracking-tight">Apply for wholesale</h1>
  <p class="mt-4 text-sm text-offwhite/70">Straight form. No fluff.</p>
  <?php if ($ok) : ?>
    <p class="mt-8 text-lg font-medium text-offwhite/85">Received. We will respond by email.</p>
  <?php else : ?>
    <?php foreach ($errors as $e) : ?>
      <p class="mt-4 text-sm text-imperial"><?= pithead_h($e) ?></p>
    <?php endforeach; ?>
    <form method="post" class="mt-10 space-y-6">
      <input type="hidden" name="csrf" value="<?= pithead_h($csrf) ?>" />
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/80">
        Business name
        <input name="business_name" required class="mt-2 w-full border border-offwhite/30 bg-coal px-4 py-3 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/80">
        Contact name
        <input name="contact_name" required class="mt-2 w-full border border-offwhite/30 bg-coal px-4 py-3 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/80">
        Email
        <input name="email" type="email" required class="mt-2 w-full border border-offwhite/30 bg-coal px-4 py-3 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/80">
        Phone
        <input name="phone" class="mt-2 w-full border border-offwhite/30 bg-coal px-4 py-3 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/80">
        Message
        <textarea name="message" required rows="6" class="mt-2 w-full border border-offwhite/30 bg-coal px-4 py-3 text-sm"></textarea>
      </label>
      <button type="submit" class="border-2 border-imperial bg-imperial px-8 py-4 text-xs font-bold uppercase tracking-widest text-offwhite hover:bg-coal">
        Submit
      </button>
    </form>
  <?php endif; ?>
</div>
<?php
pithead_layout_end();
