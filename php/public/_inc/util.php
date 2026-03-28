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

/**
 * Notify hello@pithead.co.uk when a wholesale enquiry is stored.
 * Uses PHP mail(); Reply-To is the submitter. Returns false if mail() fails (DB row still saved).
 */
function pithead_notify_wholesale_enquiry_email(
    string $business,
    string $contact,
    string $email,
    ?string $phone,
    string $message
): bool {
    $to = 'hello@pithead.co.uk';
    $subject = 'New wholesale enquiry — ' . $business;
    if (function_exists('mb_encode_mimeheader')) {
        $subject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
    }

    $phoneLine = ($phone !== null && $phone !== '') ? $phone : '(not provided)';
    $body = "A new wholesale enquiry was submitted on pithead.co.uk.\r\n\r\n"
        . "Business: {$business}\r\n"
        . "Contact name: {$contact}\r\n"
        . "Email: {$email}\r\n"
        . "Phone: {$phoneLine}\r\n\r\n"
        . "Message:\r\n{$message}\r\n";

    $from = 'Pithead Roastworks <hello@pithead.co.uk>';
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $from,
        'Reply-To: ' . $email,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    $headerStr = implode("\r\n", $headers);
    $envelopeFrom = '-fhello@pithead.co.uk';
    $ok = @mail($to, $subject, $body, $headerStr, $envelopeFrom);
    if (!$ok) {
        error_log('pithead: mail() failed sending wholesale enquiry notify to hello@pithead.co.uk');
    }

    return $ok;
}
