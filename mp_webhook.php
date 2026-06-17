<?php
ob_start();
require_once __DIR__ . '/../functions.php';

// Webhook security: verify X-Signature if set
$body   = file_get_contents('php://input');
$data   = json_decode($body, true) ?? [];
$type   = $data['type'] ?? ($_GET['type'] ?? '');
$id     = $data['data']['id'] ?? ($_GET['id'] ?? null);

// Log incoming webhook
error_log("MP Webhook: type=$type id=$id body=" . substr($body, 0, 200));

if ($type !== 'payment' || !$id) {
    http_response_code(200);
    echo 'ok';
    exit;
}

$payment = mpGetPayment((string)$id);
if (!$payment) {
    http_response_code(200);
    echo 'payment_not_found';
    exit;
}

$mpStatus = $payment['status'] ?? '';
$extRef   = $payment['external_reference'] ?? '';
$donId    = 0;

if (str_starts_with($extRef, 'don_')) {
    $donId = (int)substr($extRef, 4);
} elseif (!empty($payment['metadata']['donation_id'])) {
    $donId = (int)$payment['metadata']['donation_id'];
}

if (!$donId) {
    http_response_code(200);
    echo 'no_donation_ref';
    exit;
}

$donation = dbFetch('SELECT * FROM donations WHERE id=?', [$donId]);
if (!$donation) {
    http_response_code(200);
    echo 'donation_not_found';
    exit;
}

// Update MP data
dbUpdate('donations', [
    'mp_payment_id' => (string)$id,
    'mp_status'     => $mpStatus,
], 'id=?', [$donId]);

// Auto-approve if MP approved
if ($mpStatus === 'approved' && $donation['status'] !== 'approved') {
    $systemAdmin = dbFetch('SELECT id FROM users WHERE role="admin" ORDER BY id LIMIT 1');
    $adminId = $systemAdmin ? (int)$systemAdmin['id'] : 1;
    approveDonation($donId, $adminId);
}

http_response_code(200);
echo 'ok';
