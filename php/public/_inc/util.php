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

function pithead_format_money_cents(int $cents, string $currency): string
{
    $c = strtolower($currency);
    $amount = number_format($cents / 100, 2);
    if ($c === 'gbp') {
        return '£' . $amount;
    }

    return strtoupper($c) . ' ' . $amount;
}

/**
 * @param array<string, mixed> $order Row from orders (paid).
 * @param list<array<string, mixed>> $items Rows from order_items.
 */
function pithead_mail_order_confirmation(array $order, array $items): bool
{
    $to = trim((string) ($order['email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $ref = (string) ($order['order_number'] ?? '');
    $name = (string) ($order['name'] ?? '');
    $currency = (string) ($order['currency'] ?? 'gbp');

    $subject = 'Order confirmed — ' . $ref;
    if (function_exists('mb_encode_mimeheader')) {
        $subject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
    }

    $linesOut = [];
    foreach ($items as $it) {
        $n = (string) ($it['name_snapshot'] ?? '');
        $q = (int) ($it['quantity'] ?? 0);
        $unit = (int) ($it['unit_price_cents'] ?? 0);
        $lineTotal = $unit * $q;
        $linesOut[] = sprintf(
            '  · %s × %d  @ %s  =  %s',
            $n,
            $q,
            pithead_format_money_cents($unit, $currency),
            pithead_format_money_cents($lineTotal, $currency)
        );
    }

    $sub = pithead_format_money_cents((int) ($order['subtotal_cents'] ?? 0), $currency);
    $ship = pithead_format_money_cents((int) ($order['shipping_cents'] ?? 0), $currency);
    $tot = pithead_format_money_cents((int) ($order['total_cents'] ?? 0), $currency);

    $body = "Thanks for ordering from Pithead Roastworks.\r\n\r\n"
        . "Reference: {$ref}\r\n"
        . "Name: {$name}\r\n\r\n"
        . "Items:\r\n"
        . implode("\r\n", $linesOut)
        . "\r\n\r\n"
        . "Subtotal: {$sub}\r\n";
    if ((int) ($order['shipping_cents'] ?? 0) > 0) {
        $body .= "Postage: {$ship}\r\n";
    } else {
        $body .= "Postage: included in the prices above\r\n";
    }
    $body .= "Total: {$tot}\r\n\r\n"
        . "We will confirm dispatch when your order ships.\r\n"
        . "A separate payment receipt is emailed by Stripe.\r\n\r\n"
        . "Questions: hello@pithead.co.uk\r\n";

    $from = 'Pithead Roastworks <hello@pithead.co.uk>';
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $from,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    $headerStr = implode("\r\n", $headers);
    $envelopeFrom = '-fhello@pithead.co.uk';
    $ok = @mail($to, $subject, $body, $headerStr, $envelopeFrom);
    if (!$ok) {
        error_log('pithead: mail() failed sending order confirmation to ' . $to);
    }

    return $ok;
}

/**
 * Send a one-off order summary to the customer after payment (idempotent via confirmation_email_sent_at).
 */
function pithead_try_send_order_confirmation_email(PDO $pdo, int $orderId): void
{
    $ordSt = $pdo->prepare(
        'SELECT order_number, email, name, subtotal_cents, shipping_cents, total_cents, currency, status, confirmation_email_sent_at
         FROM orders WHERE id = ? LIMIT 1'
    );
    $ordSt->execute([$orderId]);
    $order = $ordSt->fetch(PDO::FETCH_ASSOC);
    if ($order === false || (string) ($order['status'] ?? '') !== 'paid') {
        return;
    }
    if (!empty($order['confirmation_email_sent_at'])) {
        return;
    }

    $itemSt = $pdo->prepare(
        'SELECT name_snapshot, quantity, unit_price_cents FROM order_items WHERE order_id = ? ORDER BY id ASC'
    );
    $itemSt->execute([$orderId]);
    $items = $itemSt->fetchAll(PDO::FETCH_ASSOC);
    if ($items === []) {
        return;
    }

    if (!pithead_mail_order_confirmation($order, $items)) {
        return;
    }

    $up = $pdo->prepare(
        'UPDATE orders SET confirmation_email_sent_at = NOW() WHERE id = ? AND confirmation_email_sent_at IS NULL'
    );
    $up->execute([$orderId]);
}
