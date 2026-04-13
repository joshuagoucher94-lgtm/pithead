<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/partials/link_tree_page.php';

try {
    $pdo = pithead_pdo();
    $st = $pdo->query(
        'SELECT p.* FROM products p WHERE p.is_active = 1 ORDER BY p.id ASC'
    );
    $products = $st->fetchAll();
} catch (Throwable $e) {
    pithead_shop_unavailable($e);
}

$linkPill =
    'group flex w-full flex-col items-center justify-center gap-1 rounded-2xl border border-offwhite/12 bg-offwhite/[0.07] px-5 py-3.5 text-center shadow-sm backdrop-blur-md transition hover:border-imperial/50 hover:bg-imperial hover:text-offwhite hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-imperial focus-visible:ring-offset-2 focus-visible:ring-offset-coal active:scale-[0.99]';

pithead_link_tree_page_start([
    'title' => 'Shop beans — PITHEAD ROASTWORKS',
    'description' => 'Roasted beans to take home. UK postage included. Pithead Roastworks.',
    'page_label' => 'Shop beans',
]);
?>
      <div
        class="mt-8 rounded-2xl border border-offwhite/10 bg-offwhite/[0.05] px-4 py-4 text-center backdrop-blur-sm"
      >
        <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-stone">Postage</p>
        <p class="mt-2 text-sm font-medium leading-snug text-offwhite/85">
          The price shown includes UK postage — we will confirm dispatch by email.
        </p>
      </div>

      <nav class="mt-8 flex w-full flex-col gap-3" aria-label="Coffee beans">
        <?php foreach ($products as $p) :
            $href = '/shop/' . rawurlencode((string) $p['slug']);
            $price = number_format(((int) $p['price_cents']) / 100, 2);
            $weight = (string) ($p['weight_label'] ?? '');
            $short = trim((string) ($p['short_description'] ?? ''));
            ?>
          <a href="<?= pithead_h($href) ?>" class="<?= pithead_h($linkPill) ?>">
            <span class="text-sm font-semibold uppercase tracking-[0.12em] text-offwhite"><?= pithead_h((string) $p['name']) ?></span>
            <?php if ($short !== '') : ?>
              <span class="max-w-[18rem] text-xs font-normal normal-case tracking-normal text-offwhite/65 group-hover:text-offwhite/85"><?= pithead_h($short) ?></span>
            <?php endif; ?>
            <span class="flex flex-wrap items-center justify-center gap-x-2 text-xs font-bold uppercase tracking-widest text-stone group-hover:text-offwhite/90">
              <?php if ($weight !== '') : ?>
                <span><?= pithead_h($weight) ?></span>
              <?php endif; ?>
              <span class="text-offwhite">£<?= pithead_h($price) ?></span>
            </span>
          </a>
        <?php endforeach; ?>
      </nav>
<?php
pithead_link_tree_page_end();
