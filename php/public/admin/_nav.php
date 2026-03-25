<?php
declare(strict_types=1);
$links = [
    ['/admin/', 'Dashboard'],
    ['/admin/products.php', 'Products'],
    ['/admin/orders.php', 'Orders'],
    ['/admin/wholesale.php', 'Wholesale'],
    ['/admin/contacts.php', 'Contact'],
    ['/admin/settings.php', 'Settings'],
];
?>
<header class="border-b border-offwhite/15 bg-[#0d0d0d]">
  <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4">
    <span class="text-sm font-bold uppercase tracking-tight">Pithead admin</span>
    <nav class="flex flex-wrap gap-4 text-xs font-bold uppercase tracking-widest">
      <?php foreach ($links as [$href, $label]) : ?>
        <a href="<?= pithead_h($href) ?>" class="text-offwhite/80 hover:text-imperial"><?= pithead_h($label) ?></a>
      <?php endforeach; ?>
      <a href="/" class="text-stone hover:text-offwhite">Site</a>
      <a href="/admin/logout.php" class="text-imperial">Out</a>
    </nav>
  </div>
</header>
