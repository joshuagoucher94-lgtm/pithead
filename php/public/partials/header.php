<?php
declare(strict_types=1);
$links = [
    ['/shop/', 'Shop'],
    ['/cacao/', 'Cacao'],
    ['/drinks/', 'Drinks'],
    ['/wholesale/', 'Wholesale'],
    ['/about/', 'About'],
];
?>
<header class="sticky top-0 z-50 border-b border-offwhite/20 bg-coal/95 backdrop-blur-sm">
  <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 md:px-8">
    <a href="/" class="text-lg font-bold tracking-tight text-offwhite md:text-xl">PITHEAD<span class="text-stone"> </span>ROASTWORKS</a>
    <nav class="hidden items-center gap-8 md:flex" aria-label="Primary">
      <?php foreach ($links as [$href, $label]) : ?>
        <a href="<?= pithead_h($href) ?>" class="text-sm font-semibold uppercase tracking-tight text-offwhite/90 hover:text-imperial"><?= pithead_h($label) ?></a>
      <?php endforeach; ?>
      <a href="/shop/cart.php" class="border border-offwhite/40 px-3 py-1 text-xs font-bold uppercase tracking-widest text-offwhite hover:border-imperial hover:text-imperial">Cart</a>
    </nav>
  </div>
</header>
