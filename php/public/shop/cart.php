<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/partials/layout.php';

try {
    $pdo = pithead_pdo();
    $resolved = pithead_cart_resolve($pdo);
    $lines = $resolved['lines'];
    $subtotal = $resolved['subtotal_cents'];
    $csrf = pithead_csrf_token();
} catch (Throwable $e) {
    pithead_shop_unavailable($e);
}

pithead_layout_start(['title' => 'Cart — PITHEAD ROASTWORKS', 'main_class' => 'py-16 md:py-24']);
?>
<div class="mx-auto max-w-3xl px-4 md:px-8">
  <h1 class="text-4xl font-bold uppercase tracking-tight">Cart</h1>
  <?php if ($lines === []) : ?>
    <p class="mt-8 text-offwhite/70">Empty.</p>
    <a href="/shop/" class="mt-6 inline-block border-2 border-offwhite px-6 py-3 text-xs font-bold uppercase tracking-widest hover:border-imperial">Shop</a>
  <?php else : ?>
    <form action="/api/cart-update.php" method="post" class="mt-10 space-y-6">
      <input type="hidden" name="csrf" value="<?= pithead_h($csrf) ?>" />
      <?php foreach ($lines as $l) :
          $p = $l['product'];
          $unit = number_format(((int) $p['price_cents']) / 100, 2);
          $lineTotal = number_format(((int) $p['price_cents']) * $l['qty'] / 100, 2);
          ?>
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-offwhite/15 py-6">
          <div>
            <a href="/shop/<?= pithead_h(rawurlencode((string) $p['slug'])) ?>" class="text-lg font-bold uppercase tracking-tight hover:text-imperial">
              <?= pithead_h((string) $p['name']) ?>
            </a>
            <p class="mt-1 text-sm text-offwhite/60">£<?= pithead_h($unit) ?> each</p>
          </div>
          <div class="flex items-center gap-4">
            <label class="text-xs font-bold uppercase tracking-widest text-offwhite/70">
              Qty
              <input type="number" name="qty[<?= (int) $p['id'] ?>]" value="<?= (int) $l['qty'] ?>" min="0" class="ml-2 w-16 border border-offwhite/30 bg-coal px-2 py-2" />
            </label>
            <p class="text-lg font-bold tabular-nums">£<?= pithead_h($lineTotal) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
      <div class="flex flex-wrap items-center justify-between gap-4 pt-6">
        <button type="submit" class="border border-offwhite/40 px-6 py-3 text-xs font-bold uppercase tracking-widest hover:border-imperial">Update</button>
        <p class="text-xl font-bold">Subtotal £<?= pithead_h(number_format($subtotal / 100, 2)) ?></p>
      </div>
    </form>
    <a href="/shop/checkout.php" class="mt-10 inline-block border-2 border-imperial bg-imperial px-8 py-4 text-xs font-bold uppercase tracking-widest text-offwhite hover:bg-coal">
      Checkout
    </a>
  <?php endif; ?>
</div>
<?php
pithead_layout_end();
