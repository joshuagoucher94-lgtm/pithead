<?php

declare(strict_types=1);

/**
 * JSON wholesale enquiry for modal forms on the static landing page.
 * Mirrors validation/insert logic from wholesale-apply.php.
 */
require __DIR__ . '/../_inc/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_SLASHES);
    exit;
}

if (!pithead_csrf_validate((string) ($_POST['csrf'] ?? ''))) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid or expired session. Refresh the page and try again.'], JSON_UNESCAPED_SLASHES);
    exit;
}

$business = trim((string) ($_POST['business_name'] ?? ''));
$contact = trim((string) ($_POST['contact_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($business === '' || $contact === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Please fill in all required fields with a valid email.'], JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $pdo = pithead_pdo();
    $st = $pdo->prepare(
        'INSERT INTO wholesale_enquiries (business_name, contact_name, email, phone, message) VALUES (?,?,?,?,?)'
    );
    $st->execute([
        $business,
        $contact,
        $email,
        $phone !== '' ? $phone : null,
        $message,
    ]);
    pithead_notify_wholesale_enquiry_email(
        $business,
        $contact,
        $email,
        $phone !== '' ? $phone : null,
        $message
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save your enquiry. Try again or email us.'], JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(['ok' => true], JSON_UNESCAPED_SLASHES);
