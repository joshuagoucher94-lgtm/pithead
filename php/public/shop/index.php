<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/partials/layout.php';

try {
    $pdo = pithead_pdo();
    $st = $pdo->query(
        'SELECT p.*, (SELECT path FROM product_images WHERE product_id = p.id AND is_primary = 1 ORDER BY sort_order LIMIT 1) AS image_path
         FROM products p WHERE p.is_active = 1 ORDER BY p.id ASC'
    );
    $products = $st->fetchAll();
} catch (Throwable $e) {
    pithead_shop_unavailable($e);
}

pithead_layout_start(['title' => 'Shop — PITHEAD ROASTWORKS', 'main_class' => 'py-16 md:py-24']);
?>
<div class="mx-auto max-w-7xl px-4 md:px-8">
  <h1 class="text-4xl font-bold uppercase tracking-tight md:text-5xl">Shop</h1>
  <p class="mt-4 max-w-xl text-sm font-medium text-offwhite/70">
    Roasted beans to take home. The price shown includes UK postage — we will confirm dispatch by email.
  </p>
  <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($products as $p) :
        $href = '/shop/' . rawurlencode((string) $p['slug']);
        $img = (string) ($p['image_path'] ?? '/assets/products/placeholder.svg');
        $price = number_format(((int) $p['price_cents']) / 100, 2);
        ?>
      <article class="flex flex-col border border-offwhite/15 bg-[#161616]">
        <a href="<?= pithead_h($href) ?>" class="block aspect-square border-b border-offwhite/10 bg-[#0d0d0d]">
          <img src="<?= pithead_h($img) ?>" alt="" class="h-full w-full object-contain p-6 opacity-90" loading="lazy" />
        </a>
        <div class="flex flex-1 flex-col p-6">
          <h2 class="text-xl font-bold uppercase tracking-tight">
            <a href="<?= pithead_h($href) ?>" class="hover:text-imperial"><?= pithead_h((string) $p['name']) ?></a>
          </h2>
          <p class="mt-2 flex-1 text-sm text-offwhite/70"><?= pithead_h((string) ($p['short_description'] ?? '')) ?></p>
          <p class="mt-4 text-xs font-bold uppercase tracking-widest text-stone"><?= pithead_h((string) ($p['weight_label'] ?? '')) ?></p>
          <p class="mt-2 text-lg font-bold">£<?= pithead_h($price) ?></p>
          <form action="/api/cart-add.php" method="post" class="mt-6">
            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>" />
            <input type="hidden" name="qty" value="1" />
            <input type="hidden" name="csrf" value="<?= pithead_h(pithead_csrf_token()) ?>" />
            <input type="hidden" name="redirect" value="/shop/cart.php" />
            <button type="submit" class="w-full border-2 border-offwhite py-3 text-xs font-bold uppercase tracking-widest hover:border-imperial hover:text-imperial">
              Add to cart
            </button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
<?php
pithead_layout_end();
