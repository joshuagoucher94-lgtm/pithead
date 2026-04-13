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
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    try {
        $pdo = new PDO($dsn, $user, $pass, $opts);
    } catch (PDOException $e) {
        // Shared hosts (e.g. Hostinger) often expose MySQL on a Unix socket for "localhost"
        // while TCP to 127.0.0.1 is refused — config copied from local dev hits this.
        $msg = $e->getMessage();
        $tcpRefused = str_contains($msg, '2002') || str_contains($msg, 'Connection refused');
        $canRetryLocalhost = $tcpRefused
            && $host === '127.0.0.1'
            && (!is_string($socket) || $socket === '');
        if ($canRetryLocalhost) {
            $dsnLocal = "mysql:host=localhost;port={$port};dbname={$name};charset={$charset}";
            try {
                $pdo = new PDO($dsnLocal, $user, $pass, $opts);
            } catch (PDOException) {
                throw $e;
            }
        } else {
            throw $e;
        }
    }
    return $pdo;
}
