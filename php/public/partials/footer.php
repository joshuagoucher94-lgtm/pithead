<?php
declare(strict_types=1);
$year = (int) date('Y');
?>
<footer class="border-t border-offwhite/20 bg-coal">
  <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 md:grid-cols-2 md:px-8 lg:grid-cols-3">
    <div>
      <p class="text-xs font-bold uppercase tracking-widest text-stone">Brand</p>
      <p class="mt-2 text-lg font-bold tracking-tight">PITHEAD ROASTWORKS</p>
      <p class="mt-2 max-w-xs text-sm text-offwhite/70">Marks work. Does not decorate it.</p>
    </div>
    <div>
      <p class="text-xs font-bold uppercase tracking-widest text-stone">Social</p>
      <ul class="mt-4 space-y-2 text-sm font-semibold uppercase tracking-tight">
        <li><a href="https://instagram.com/" class="hover:text-imperial" rel="noopener noreferrer">Instagram</a></li>
      </ul>
    </div>
    <div>
      <p class="text-xs font-bold uppercase tracking-widest text-stone">Location</p>
      <p class="mt-4 text-sm text-offwhite/80">See contact for address.</p>
    </div>
  </div>
  <div class="border-t border-offwhite/10 px-4 py-6 text-center text-xs text-offwhite/50 md:px-8">
    © <?= $year ?> PITHEAD ROASTWORKS
  </div>
</footer>
