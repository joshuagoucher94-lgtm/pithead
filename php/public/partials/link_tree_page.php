<?php

declare(strict_types=1);

/**
 * Full-page shell matching the Astro landing (link tree): background, narrow column, no sticky header.
 *
 * @param array{title: string, description?: string, page_label?: string} $opts
 */
function pithead_link_tree_page_start(array $opts): void
{
    $title = (string) $opts['title'];
    $description = (string) ($opts['description'] ?? 'PITHEAD ROASTWORKS — Engineered espresso. Fuel for the shift.');
    $pageLabel = isset($opts['page_label']) ? (string) $opts['page_label'] : '';
    ?>
<!DOCTYPE html>
<html lang="en" class="min-h-dvh">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="<?= pithead_h($description) ?>" />
  <meta name="robots" content="index, follow" />
  <meta name="theme-color" content="#111111" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/assets/app.css" />
  <title><?= pithead_h($title) ?></title>
</head>
<body class="min-h-dvh overflow-x-hidden overflow-y-auto bg-coal font-sans text-offwhite antialiased">
  <div class="relative min-h-dvh">
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 z-0">
      <img
        src="/images/espresso-pour.jpg"
        alt=""
        class="h-full w-full scale-105 object-cover opacity-[0.14]"
        width="1920"
        height="1280"
        loading="eager"
        fetchpriority="high"
      />
      <div class="absolute inset-0 bg-gradient-to-b from-coal via-coal to-[#0a0a0a]"></div>
      <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(122,31,31,0.25),transparent)]"></div>
    </div>

    <div class="relative z-10 mx-auto flex min-h-dvh max-w-md flex-col px-5 pb-10 pt-10 sm:px-6 sm:pt-14">
      <h1 class="sr-only"><?= pithead_h($title) ?></h1>

      <header class="flex flex-col items-center text-center">
        <a
          href="/"
          class="group rounded-2xl bg-white p-4 shadow-[0_20px_50px_-12px_rgba(0,0,0,0.45)] ring-1 ring-black/10 transition hover:shadow-[0_25px_60px_-12px_rgba(0,0,0,0.55)]"
          aria-label="Pithead Roastworks home"
        >
          <img
            src="/images/pithead-logo-stacked.png"
            alt="Pithead Roastworks"
            class="mx-auto h-[4.5rem] w-auto sm:h-[5.25rem]"
            width="180"
            height="80"
            loading="eager"
          />
        </a>
        <?php if ($pageLabel !== '') : ?>
          <p class="mt-5 max-w-[16rem] text-[11px] font-semibold uppercase leading-relaxed tracking-[0.28em] text-stone sm:max-w-none sm:text-xs sm:tracking-[0.32em]">
            <?= pithead_h($pageLabel) ?>
          </p>
        <?php endif; ?>
        <div class="mt-6 h-px w-10 bg-imperial/50" aria-hidden="true"></div>
      </header>
<?php
}

function pithead_link_tree_page_end(): void
{
    ?>
    </div>
  </div>
</body>
</html>
<?php
}
