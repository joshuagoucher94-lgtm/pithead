<?php

declare(strict_types=1);

/**
 * Issue a CSRF token for same-origin clients (e.g. landing page modals).
 * GET only — starts session via bootstrap.
 */
require __DIR__ . '/../_inc/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

echo json_encode(['csrf' => pithead_csrf_token()], JSON_UNESCAPED_SLASHES);
