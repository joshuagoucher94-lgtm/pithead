<?php

declare(strict_types=1);

/**
 * Copy to config.local.php (same directory). Never commit secrets.
 */
return [
    'env' => 'development',
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'pithead',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
        // Optional: macOS Homebrew MySQL often uses a socket; if TCP gives "Connection refused", try e.g.:
        // 'unix_socket' => '/tmp/mysql.sock',
        // or: '/opt/homebrew/var/mysql/mysql.sock' (Apple Silicon) / '/usr/local/var/mysql/mysql.sock' (Intel)
    ],
    'stripe' => [
        'secret_key' => 'sk_test_...',
        'publishable_key' => 'pk_test_...',
        'webhook_secret' => 'whsec_...',
    ],
    'app' => [
        'base_url' => 'https://yourdomain.com',
        'currency' => 'gbp',
    ],
    // Optional: long random string. While set, GET /api/health-db.php?key=THAT_STRING
    // returns JSON (DB connect + products row count). Remove after debugging.
    'health_check_key' => null,
];
