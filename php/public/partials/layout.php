<?php

declare(strict_types=1);

/**
 * @param array{title?: string, main_class?: string} $opts
 */
function pithead_layout_start(array $opts = []): void
{
    $title = (string) ($opts['title'] ?? 'PITHEAD ROASTWORKS');
    $mainClass = (string) ($opts['main_class'] ?? '');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/assets/app.css" />
  <title><?= pithead_h($title) ?></title>
</head>
<body class="min-h-screen flex flex-col bg-coal text-offwhite font-sans antialiased">
<?php require __DIR__ . '/header.php'; ?>
<main class="flex-1 <?= pithead_h($mainClass) ?>">
<?php
}

function pithead_layout_end(): void
{
    ?>
</main>
<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
<?php
}
