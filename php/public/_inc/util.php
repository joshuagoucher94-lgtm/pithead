<?php

declare(strict_types=1);

function pithead_h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function pithead_redirect(string $url, int $code = 302): never
{
    header('Location: ' . $url, true, $code);
    exit;
}

function pithead_json_response(array $data, int $code = 200): never
{
    header('Content-Type: application/json; charset=utf-8', true, $code);
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

function pithead_base_url(): string
{
    $c = pithead_config();
    $base = (string) ($c['app']['base_url'] ?? '');
    if ($base !== '') {
        return rtrim($base, '/');
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return ($https ? 'https://' : 'http://') . $host;
}

function pithead_setting(PDO $pdo, string $key, ?string $default = null): ?string
{
    $st = $pdo->prepare('SELECT `value` FROM site_settings WHERE `key` = ? LIMIT 1');
    $st->execute([$key]);
    $row = $st->fetch();
    if ($row === false) {
        return $default;
    }
    return $row['value'] !== null ? (string) $row['value'] : $default;
}
