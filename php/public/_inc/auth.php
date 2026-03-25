<?php

declare(strict_types=1);

function pithead_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $pdo = pithead_pdo();
    $st = $pdo->prepare('SELECT id, email FROM admins WHERE id = ? LIMIT 1');
    $st->execute([(int) $_SESSION['admin_id']]);
    $row = $st->fetch();
    return $row !== false ? $row : null;
}

function pithead_require_admin(): void
{
    if (pithead_admin() === null) {
        pithead_redirect('/admin/login.php');
    }
}

function pithead_admin_login(PDO $pdo, string $email, string $password): bool
{
    $st = $pdo->prepare('SELECT id, password_hash FROM admins WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $row = $st->fetch();
    if ($row === false) {
        return false;
    }
    if (!password_verify($password, (string) $row['password_hash'])) {
        return false;
    }
    $_SESSION['admin_id'] = (int) $row['id'];
    return true;
}

function pithead_admin_logout(): void
{
    unset($_SESSION['admin_id']);
}
