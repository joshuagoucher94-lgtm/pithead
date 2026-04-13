<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/partials/layout.php';

$slug = (string) ($_GET['slug'] ?? '');
if ($slug === '') {
    pithead_redirect('/shop/');
}

try {
    $pdo = pithead_pdo();
    $st = $pdo->prepare(
        'SELECT p.*, c.slug AS category_slug, c.name AS category_name FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.slug = ? AND p.is_active = 1 LIMIT 1'
    );
    $st->execute([$slug]);
    $p = $st->fetch();
    if ($p === false) {
        http_response_code(404);
        pithead_layout_start(['title' => 'Not found', 'main_class' => 'py-24']);
        echo '<div class="mx-auto max-w-7xl px-4"><p class="text-offwhite/80">Product not found.</p><a class="mt-4 inline-block text-imperial" href="/shop/">Shop</a></div>';
        pithead_layout_end();
        exit;
    }

    $imgSt = $pdo->prepare('SELECT path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC');
    $imgSt->execute([(int) $p['id']]);
    $images = $imgSt->fetchAll();
    $primary = $images[0]['path'] ?? '/assets/products/placeholder.svg';

    $relSt = $pdo->prepare(
        'SELECT slug, name, price_cents FROM products WHERE is_active = 1 AND id != ? AND category_id = ? ORDER BY id ASC LIMIT 3'
    );
    $relSt->execute([(int) $p['id'], (int) $p['category_id']]);
    $related = $relSt->fetchAll();

    $specs = [];
    if (!empty($p['specs'])) {
        $decoded = json_decode((string) $p['specs'], true);
        if (is_array($decoded)) {
            $specs = $decoded;
        }
    }

    $price = number_format(((int) $p['price_cents']) / 100, 2);
} catch (Throwable $e) {
    pithead_shop_unavailable($e);
}

pithead_layout_start(['title' => (string) $p['name'] . ' — PITHEAD', 'main_class' => 'py-16 md:py-24']);
?>
<div class="mx-auto max-w-7xl px-4 md:px-8">
  <div class="grid gap-12 lg:grid-cols-2">
    <div class="border border-offwhite/15 bg-[#0d0d0d]">
      <img src="<?= pithead_h((string) $primary) ?>" alt="" class="aspect-square w-full object-contain p-8" />
    </div>
    <div>
      <p class="text-xs font-bold uppercase tracking-widest text-stone"><?= pithead_h((string) ($p['category_name'] ?? '')) ?></p>
      <h1 class="mt-4 text-4xl font-bold uppercase tracking-tight md:text-5xl"><?= pithead_h((string) $p['name']) ?></h1>
      <?php if (($p['tagline'] ?? '') !== '') : ?>
        <p class="mt-4 text-lg font-semibold text-offwhite/85"><?= pithead_h((string) $p['tagline']) ?></p>
      <?php endif; ?>
      <p class="mt-6 text-2xl font-bold">£<?= pithead_h($price) ?></p>
      <form action="/api/cart-add.php" method="post" class="mt-8 flex flex-wrap gap-4">
        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>" />
        <input type="hidden" name="csrf" value="<?= pithead_h(pithead_csrf_token()) ?>" />
        <input type="hidden" name="redirect" value="/shop/cart.php" />
        <label class="flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-offwhite/80">
          Qty
          <input type="number" name="qty" value="1" min="1" class="w-20 border border-offwhite/30 bg-coal px-2 py-2 text-offwhite" />
        </label>
        <button type="submit" class="border-2 border-imperial bg-imperial px-8 py-3 text-xs font-bold uppercase tracking-widest text-offwhite hover:bg-coal">
          Add to cart
        </button>
      </form>
      <?php if (($p['short_description'] ?? '') !== '') : ?>
        <p class="mt-10 text-lg font-medium text-offwhite/80"><?= pithead_h((string) $p['short_description']) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <?php if (($p['brew_suggestions'] ?? '') !== '') : ?>
    <section class="mt-20 border-t border-offwhite/10 pt-12">
      <h2 class="text-sm font-bold uppercase tracking-widest text-imperial">Brew</h2>
      <p class="mt-4 max-w-2xl text-sm font-medium text-offwhite/75 whitespace-pre-line"><?= pithead_h((string) $p['brew_suggestions']) ?></p>
    </section>
  <?php endif; ?>

  <?php if ($specs !== []) : ?>
    <section class="mt-12 border-t border-offwhite/10 pt-12">
      <h2 class="text-sm font-bold uppercase tracking-widest text-imperial">Specs</h2>
      <dl class="mt-4 grid gap-2 sm:grid-cols-2">
        <?php foreach ($specs as $k => $v) : ?>
          <div class="flex justify-between border-b border-offwhite/10 py-2 text-sm">
            <dt class="font-semibold uppercase tracking-tight text-offwhite/60"><?= pithead_h((string) $k) ?></dt>
            <dd class="text-offwhite"><?= pithead_h((string) $v) ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>
    </section>
  <?php endif; ?>

  <?php if ($related !== []) : ?>
    <section class="mt-20 border-t border-offwhite/10 pt-12">
      <h2 class="text-xl font-bold uppercase tracking-tight">Related</h2>
      <ul class="mt-6 space-y-3">
        <?php foreach ($related as $r) :
            $rp = number_format(((int) $r['price_cents']) / 100, 2);
            ?>
          <li>
            <a href="/shop/<?= pithead_h(rawurlencode((string) $r['slug'])) ?>" class="text-sm font-semibold uppercase tracking-tight text-offwhite hover:text-imperial">
              <?= pithead_h((string) $r['name']) ?> — £<?= pithead_h($rp) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>
</div>
<?php
pithead_layout_end();
