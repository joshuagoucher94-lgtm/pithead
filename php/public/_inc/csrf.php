<?php

declare(strict_types=1);

function pithead_csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['_csrf'];
}

function pithead_csrf_validate(?string $token): bool
{
    $expected = $_SESSION['_csrf'] ?? '';
    return is_string($token) && $expected !== '' && hash_equals($expected, $token);
}
