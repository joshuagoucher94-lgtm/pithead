<?php

declare(strict_types=1);

/** Application bootstrap: `public/_inc/` is not web-accessible (see `.htaccess`). */

if (!defined('PITHEAD_INC')) {
    define('PITHEAD_INC', __DIR__);
}
if (!defined('PITHEAD_ROOT')) {
    define('PITHEAD_ROOT', dirname(PITHEAD_INC));
}

$configFile = PITHEAD_INC . '/config.local.php';
if (is_readable($configFile)) {
    /** @var array<string, mixed> $pitheadConfig */
    $pitheadConfig = require $configFile;
} else {
    $pitheadConfig = [];
}

if (!defined('PITHEAD_ENV')) {
    define('PITHEAD_ENV', (string) ($pitheadConfig['env'] ?? 'production'));
}

$logDir = PITHEAD_INC . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
if (is_dir($logDir) && is_writable($logDir)) {
    ini_set('log_errors', '1');
    ini_set('error_log', $logDir . '/php-errors.log');
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once PITHEAD_INC . '/db.php';
require_once PITHEAD_INC . '/util.php';
require_once PITHEAD_INC . '/csrf.php';
require_once PITHEAD_INC . '/cart.php';
require_once PITHEAD_INC . '/orders.php';
