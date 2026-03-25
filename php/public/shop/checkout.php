<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/_inc/stripe_init.php';
require_once dirname(__DIR__) . '/partials/layout.php';

$pdo = pithead_pdo();
$resolved = pithead_cart_resolve($pdo);
if ($resolved['lines'] === []) {
    pithead_redirect('/shop/cart.php');
}

$pk = pithead_stripe_config()['publishable_key'];
$csrf = pithead_csrf_token();

pithead_layout_start(['title' => 'Checkout — PITHEAD ROASTWORKS', 'main_class' => 'py-16 md:py-24']);
?>
<div class="mx-auto max-w-3xl px-4 md:px-8">
  <h1 class="text-4xl font-bold uppercase tracking-tight">Checkout</h1>
  <p class="mt-4 text-sm text-offwhite/60">Embedded Stripe. No redirect.</p>

  <div class="mt-10 space-y-4 border border-offwhite/15 p-6">
    <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/80">
      Email
      <input type="email" id="ch-email" required class="mt-2 w-full border border-offwhite/30 bg-coal px-4 py-3 text-sm text-offwhite" />
    </label>
    <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/80">
      Name
      <input type="text" id="ch-name" required class="mt-2 w-full border border-offwhite/30 bg-coal px-4 py-3 text-sm text-offwhite" />
    </label>
    <button type="button" id="ch-start" class="mt-4 border-2 border-imperial bg-imperial px-8 py-4 text-xs font-bold uppercase tracking-widest text-offwhite hover:bg-coal disabled:opacity-40" <?= $pk === '' ? 'disabled' : '' ?>>
      Load payment
    </button>
    <p id="ch-err" class="hidden text-sm text-imperial"></p>
    <?php if ($pk === '') : ?>
      <p class="mt-4 text-sm text-imperial">Configure stripe.publishable_key in _inc/config.local.php.</p>
    <?php endif; ?>
  </div>

  <div id="checkout" class="mt-10 min-h-[480px] border border-offwhite/15 bg-[#0a0a0a] p-4"></div>
</div>
<?php if ($pk !== '') : ?>
<script src="https://js.stripe.com/v3/"></script>
<script>
  (function () {
    const pk = <?= json_encode($pk, JSON_THROW_ON_ERROR) ?>;
    const csrf = <?= json_encode($csrf, JSON_THROW_ON_ERROR) ?>;
    const mountEl = document.getElementById('checkout');
    const errEl = document.getElementById('ch-err');
    const btn = document.getElementById('ch-start');
    let checkout = null;
    btn.addEventListener('click', async function () {
      errEl.classList.add('hidden');
      const email = document.getElementById('ch-email').value.trim();
      const name = document.getElementById('ch-name').value.trim();
      if (!email || !name) {
        errEl.textContent = 'Email and name required.';
        errEl.classList.remove('hidden');
        return;
      }
      btn.disabled = true;
      try {
        const res = await fetch('/api/create-embedded-checkout-session.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, name, csrf }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Failed');
        const stripe = Stripe(data.publishableKey || pk);
        if (checkout) {
          checkout.destroy();
          checkout = null;
        }
        checkout = await stripe.initEmbeddedCheckout({ clientSecret: data.clientSecret });
        checkout.mount('#checkout');
      } catch (e) {
        errEl.textContent = e.message || 'Error';
        errEl.classList.remove('hidden');
        btn.disabled = false;
      }
    });
  })();
</script>
<?php endif; ?>
<?php
pithead_layout_end();
