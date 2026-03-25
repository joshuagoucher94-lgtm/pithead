<?php

declare(strict_types=1);

/**
 * Temporary DB diagnostic (Hostinger / production 500s).
 * Set health_check_key in config.local.php, visit once with ?key=..., then remove the key.
 */
require_once dirname(__DIR__) . '/_inc/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$c = pithead_config();
$expected = $c['health_check_key'] ?? null;
$provided = (string) ($_GET['key'] ?? '');
if (!is_string($expected) || $expected === '' || !hash_equals($expected, $provided)) {
    http_response_code(404);
    exit;
}

try {
    $pdo = pithead_pdo();
    $pdo->query('SELECT 1');
    $st = $pdo->query('SELECT COUNT(*) AS n FROM products');
    $row = $st->fetch();
    $n = (int) ($row['n'] ?? 0);
    echo json_encode(['ok' => true, 'products_count' => $n], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ], JSON_THROW_ON_ERROR);
}
