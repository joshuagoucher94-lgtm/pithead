<?php

declare(strict_types=1);

/**
 * Development router for `php -S` (ignores .htaccess).
 * Run from the merged deploy/ folder:
 *
 *   ./scripts/build-deploy.sh
 *   cd deploy && php -S 127.0.0.1:8080 ../scripts/dev-router.php
 */
$root = getcwd();
if (!is_file($root . '/index.html') && !is_dir($root . '/shop')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Run from the deploy/ directory after ./scripts/build-deploy.sh\n";
    echo "Example: cd deploy && php -S 127.0.0.1:8080 ../scripts/dev-router.php\n";
    exit(1);
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Pre-launch: mirror php/public/.htaccess — storefront redirects to /. Remove when launching.
if (preg_match('#^/shop(/.*)?$#', $uri)) {
    header('Location: /', true, 301);
    exit;
}

// Static file (css, js, images, .php served as files)
if ($uri !== '/' && $uri !== '') {
    $file = $root . $uri;
    if (is_file($file)) {
        return false;
    }
}

// Apache-style: /admin → admin/index.php
if (preg_match('#^/admin/?$#', $uri)) {
    chdir($root);
    require $root . '/admin/index.php';
    return true;
}

// Shop listing: /shop or /shop/ → shop/index.php (no .htaccess on php -S)
if (preg_match('#^/shop/?$#', $uri)) {
    chdir($root);
    require $root . '/shop/index.php';
    return true;
}

// Pretty product URLs (matches public/.htaccess)
if (preg_match('#^/shop/([a-z0-9-]+)/?$#', $uri, $m)) {
    chdir($root);
    $_GET['slug'] = $m[1];
    require $root . '/shop/product.php';
    return true;
}

// Astro output: /about/ → about/index.html
$candidates = [];
if (str_ends_with($uri, '/')) {
    $candidates[] = $root . $uri . 'index.html';
} else {
    $candidates[] = $root . $uri . '/index.html';
    $candidates[] = $root . $uri . '.html';
}
foreach ($candidates as $html) {
    if (is_file($html)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($html);
        return true;
    }
}

// Root
if ($uri === '/' || $uri === '') {
    $idx = $root . '/index.html';
    if (is_file($idx)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($idx);
        return true;
    }
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo '404 ' . $uri;

return true;
