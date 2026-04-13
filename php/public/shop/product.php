<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/partials/link_tree_page.php';

$slug = (string) ($_GET['slug'] ?? '');
if ($slug === '') {
    pithead_redirect('/shop/');
}

$secondaryPill =
    'flex w-full items-center justify-center rounded-2xl border border-offwhite/12 bg-offwhite/[0.07] px-5 py-3.5 text-center text-sm font-semibold uppercase tracking-[0.12em] text-offwhite shadow-sm backdrop-blur-md transition hover:border-imperial/50 hover:bg-imperial hover:text-offwhite hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-imperial focus-visible:ring-offset-2 focus-visible:ring-offset-coal active:scale-[0.99]';

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
        pithead_link_tree_page_start([
            'title' => 'Not found — PITHEAD ROASTWORKS',
            'description' => 'Product not found.',
            'page_label' => 'Shop beans',
        ]);
        ?>
      <p class="mt-8 text-center text-sm text-offwhite/75">Product not found.</p>
      <nav class="mt-6 flex w-full flex-col gap-3" aria-label="Shop">
        <a href="/shop/" class="<?= pithead_h($secondaryPill) ?>">All beans</a>
      </nav>
        <?php
        pithead_link_tree_page_end();
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

$brew = trim((string) ($p['brew_suggestions'] ?? ''));
$hasMore = $brew !== '' || $specs !== [] || $related !== [];

pithead_link_tree_page_start([
    'title' => (string) $p['name'] . ' — PITHEAD ROASTWORKS',
    'description' => (string) ($p['short_description'] ?? ''),
    'page_label' => 'Shop beans',
]);
?>
      <nav class="mt-8 flex w-full flex-col gap-3" aria-label="Shop navigation">
        <a href="/shop/" class="<?= pithead_h($secondaryPill) ?>">All beans</a>
      </nav>

      <div class="mt-6 overflow-hidden rounded-2xl border border-offwhite/15 bg-[#0d0d0d] ring-1 ring-black/40">
        <img src="<?= pithead_h((string) $primary) ?>" alt="" class="aspect-square w-full object-contain p-6 opacity-90" />
      </div>

      <div class="mt-6 text-center">
        <p class="text-xs font-bold uppercase tracking-widest text-stone"><?= pithead_h((string) ($p['category_name'] ?? '')) ?></p>
        <h2 class="mt-3 text-2xl font-bold uppercase tracking-tight text-offwhite"><?= pithead_h((string) $p['name']) ?></h2>
        <?php if (($p['tagline'] ?? '') !== '') : ?>
          <p class="mt-2 text-sm font-semibold text-offwhite/80"><?= pithead_h((string) $p['tagline']) ?></p>
        <?php endif; ?>
        <p class="mt-4 text-xl font-bold tabular-nums">£<?= pithead_h($price) ?></p>
      </div>

      <form action="/api/cart-add.php" method="post" class="mt-6 flex flex-col gap-4">
        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>" />
        <input type="hidden" name="csrf" value="<?= pithead_h(pithead_csrf_token()) ?>" />
        <input type="hidden" name="redirect" value="/shop/cart.php" />
        <label class="block text-[10px] font-bold uppercase tracking-widest text-stone">
          Qty
          <input
            type="number"
            name="qty"
            value="1"
            min="1"
            class="mt-1.5 w-full rounded-xl border border-offwhite/20 bg-offwhite/[0.06] px-3 py-2.5 text-sm text-offwhite focus:border-imperial/60 focus:outline-none focus:ring-1 focus:ring-imperial"
          />
        </label>
        <button
          type="submit"
          class="w-full rounded-2xl border-2 border-imperial bg-imperial py-3.5 text-xs font-bold uppercase tracking-[0.15em] text-offwhite shadow-[0_12px_40px_-10px_rgba(122,31,31,0.75)] ring-2 ring-offwhite/20 transition hover:border-offwhite/50 hover:bg-[#8f2828] hover:shadow-[0_16px_48px_-10px_rgba(122,31,31,0.85)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-imperial focus-visible:ring-offset-2 focus-visible:ring-offset-coal active:scale-[0.99]"
        >
          Add to cart
        </button>
      </form>

      <?php if (($p['short_description'] ?? '') !== '') : ?>
        <p class="mt-6 text-center text-sm font-medium leading-relaxed text-offwhite/75"><?= pithead_h((string) $p['short_description']) ?></p>
      <?php endif; ?>

      <?php if ($hasMore) : ?>
        <div class="mt-8 flex w-full flex-col gap-2">
          <?php if ($brew !== '') : ?>
            <details class="group rounded-2xl border border-offwhite/10 bg-offwhite/[0.05] px-4 py-3 backdrop-blur-sm">
              <summary class="cursor-pointer list-none text-xs font-bold uppercase tracking-widest text-stone marker:content-none [&::-webkit-details-marker]:hidden">
                <span class="flex items-center justify-between gap-2">
                  Brew
                  <span class="text-offwhite/40 transition group-open:rotate-180" aria-hidden="true">▼</span>
                </span>
              </summary>
              <p class="mt-3 whitespace-pre-line text-left text-sm font-medium leading-relaxed text-offwhite/75"><?= pithead_h($brew) ?></p>
            </details>
          <?php endif; ?>

          <?php if ($specs !== []) : ?>
            <details class="group rounded-2xl border border-offwhite/10 bg-offwhite/[0.05] px-4 py-3 backdrop-blur-sm">
              <summary class="cursor-pointer list-none text-xs font-bold uppercase tracking-widest text-stone marker:content-none [&::-webkit-details-marker]:hidden">
                <span class="flex items-center justify-between gap-2">
                  Specs
                  <span class="text-offwhite/40 transition group-open:rotate-180" aria-hidden="true">▼</span>
                </span>
              </summary>
              <dl class="mt-3 space-y-2 text-left text-sm">
                <?php foreach ($specs as $k => $v) : ?>
                  <div class="flex justify-between gap-4 border-b border-offwhite/10 py-2 last:border-0">
                    <dt class="font-semibold uppercase tracking-tight text-offwhite/55"><?= pithead_h((string) $k) ?></dt>
                    <dd class="text-offwhite"><?= pithead_h((string) $v) ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
            </details>
          <?php endif; ?>

          <?php if ($related !== []) : ?>
            <details class="group rounded-2xl border border-offwhite/10 bg-offwhite/[0.05] px-4 py-3 backdrop-blur-sm">
              <summary class="cursor-pointer list-none text-xs font-bold uppercase tracking-widest text-stone marker:content-none [&::-webkit-details-marker]:hidden">
                <span class="flex items-center justify-between gap-2">
                  Related
                  <span class="text-offwhite/40 transition group-open:rotate-180" aria-hidden="true">▼</span>
                </span>
              </summary>
              <ul class="mt-3 flex flex-col gap-2 text-left">
                <?php foreach ($related as $r) :
                    $rp = number_format(((int) $r['price_cents']) / 100, 2);
                    ?>
                  <li>
                    <a
                      href="/shop/<?= pithead_h(rawurlencode((string) $r['slug'])) ?>"
                      class="block rounded-xl border border-transparent px-2 py-2 text-sm font-semibold uppercase tracking-tight text-offwhite transition hover:border-offwhite/15 hover:bg-offwhite/[0.06]"
                    >
                      <?= pithead_h((string) $r['name']) ?> — £<?= pithead_h($rp) ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </details>
          <?php endif; ?>
        </div>
      <?php endif; ?>
<?php
pithead_link_tree_page_end();
