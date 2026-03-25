<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
function pithead_config(): array
{
    static $cfg;
    if ($cfg !== null) {
        return $cfg;
    }
    $file = PITHEAD_INC . '/config.local.php';
    $cfg = is_readable($file) ? require $file : [];
    return $cfg;
}

function pithead_pdo(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = pithead_config();
    $db = $c['db'] ?? [];
    $host = (string) ($db['host'] ?? '127.0.0.1');
    $port = (int) ($db['port'] ?? 3306);
    $name = (string) ($db['name'] ?? 'pithead');
    $user = (string) ($db['user'] ?? 'root');
    $pass = (string) ($db['pass'] ?? '');
    $charset = (string) ($db['charset'] ?? 'utf8mb4');
    $socket = $db['unix_socket'] ?? null;
    if (is_string($socket) && $socket !== '') {
        $dsn = "mysql:unix_socket={$socket};dbname={$name};charset={$charset}";
    } else {
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    }
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}
