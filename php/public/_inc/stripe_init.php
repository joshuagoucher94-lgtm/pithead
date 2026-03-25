<?php

declare(strict_types=1);

function pithead_stripe_autoload(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $init = PITHEAD_INC . '/vendor/stripe-php/init.php';
    if (!is_readable($init)) {
        throw new RuntimeException('Stripe SDK missing: ensure _inc/vendor/stripe-php exists.');
    }
    require_once $init;
    $loaded = true;
}

/**
 * @return array{secret_key: string, publishable_key: string, webhook_secret: string}
 */
function pithead_stripe_config(): array
{
    $c = pithead_config();
    $s = $c['stripe'] ?? [];
    return [
        'secret_key' => (string) ($s['secret_key'] ?? ''),
        'publishable_key' => (string) ($s['publishable_key'] ?? ''),
        'webhook_secret' => (string) ($s['webhook_secret'] ?? ''),
    ];
}

function pithead_stripe_client(): \Stripe\StripeClient
{
    pithead_stripe_autoload();
    $sk = pithead_stripe_config()['secret_key'];
    if ($sk === '') {
        throw new RuntimeException('stripe.secret_key not configured.');
    }
    return new \Stripe\StripeClient($sk);
}
