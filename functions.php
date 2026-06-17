<?php
require_once __DIR__ . '/auth.php';

function mpCreatePreference(array $items, float $amount, int $userId, int $donationId): array {
    $token    = MP_ACCESS_TOKEN;
    $baseUrl  = BASE_URL;

    $body = [
        'items' => $items,
        'payer'  => [],
        'back_urls' => [
            'success' => "$baseUrl/api/mp_return.php?status=success&did=$donationId",
            'failure' => "$baseUrl/api/mp_return.php?status=failure&did=$donationId",
            'pending' => "$baseUrl/api/mp_return.php?status=pending&did=$donationId",
        ],
        'auto_return'           => 'approved',
        'notification_url'      => "$baseUrl/api/mp_webhook.php",
        'external_reference'    => "don_$donationId",
        'statement_descriptor'  => 'CooperativaFLB',
        'metadata'              => ['donation_id' => $donationId, 'user_id' => $userId],
    ];

    $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            "Authorization: Bearer $token",
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($httpCode !== 201 || empty($data['id'])) {
        return ['ok' => false, 'error' => $data['message'] ?? 'Error MP'];
    }
    $initPoint = (MP_MODE === 'sandbox') ? $data['sandbox_init_point'] : $data['init_point'];
    return ['ok' => true, 'preference_id' => $data['id'], 'init_point' => $initPoint];
}

function mpGetPayment(string $paymentId): ?array {
    $token = MP_ACCESS_TOKEN;
    $ch = curl_init("https://api.mercadopago.com/v1/payments/$paymentId");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function createDonationRecord(int $userId, float $amount, ?int $campaignId, string $method = 'mercadopago'): int {
    return (int) dbInsert('donations', [
        'user_id'        => $userId,
        'campaign_id'    => $campaignId,
        'amount'         => $amount,
        'status'         => 'pending',
        'payment_method' => $method,
        'receipt_token'  => generateToken(),
        'created_at'     => date('Y-m-d H:i:s'),
    ]);
}

function approveDonation(int $donationId, int $adminId): void {
    $donation = dbFetch('SELECT * FROM donations WHERE id=?', [$donationId]);
    if (!$donation) return;

    dbUpdate('donations', [
        'status'       => 'approved',
        'confirmed_at' => date('Y-m-d H:i:s'),
        'confirmed_by' => $adminId,
    ], 'id=?', [$donationId]);

    if ($donation['campaign_id']) {
        dbQuery('UPDATE campaigns SET current_amount = current_amount + ? WHERE id=?',
            [$donation['amount'], $donation['campaign_id']]);
    }
    adminLog($adminId, 'approve_donation', 'donation', $donationId, money($donation['amount']));
}

function rejectDonation(int $donationId, int $adminId, string $reason): void {
    dbUpdate('donations', [
        'status'           => 'rejected',
        'rejection_reason' => $reason,
    ], 'id=?', [$donationId]);
    adminLog($adminId, 'reject_donation', 'donation', $donationId, $reason);
}

function getTotalRecaudado(): float {
    $row = dbFetch('SELECT COALESCE(SUM(amount),0) AS total FROM donations WHERE status="approved"');
    return (float)($row['total'] ?? 0);
}

function getStats(): array {
    $total   = getTotalRecaudado();
    $donors  = dbFetch('SELECT COUNT(DISTINCT user_id) AS c FROM donations WHERE status="approved"')['c'] ?? 0;
    $month   = dbFetch('SELECT COALESCE(SUM(amount),0) AS t FROM donations WHERE status="approved" AND MONTH(confirmed_at)=MONTH(NOW()) AND YEAR(confirmed_at)=YEAR(NOW())')['t'] ?? 0;
    $camps   = dbFetch('SELECT COUNT(*) AS c FROM campaigns WHERE status="active"')['c'] ?? 0;
    $pending_users = dbFetch('SELECT COUNT(*) AS c FROM users WHERE status="pending"')['c'] ?? 0;
    $pending_dons  = dbFetch('SELECT COUNT(*) AS c FROM donations WHERE status="pending"')['c'] ?? 0;
    return compact('total','donors','month','camps','pending_users','pending_dons');
}

/**
 * Devuelve campañas activas en estructura de árbol (padre => [hijos])
 */
function getCampaignTree(): array {
    $all = dbFetchAll('SELECT * FROM campaigns WHERE status="active" ORDER BY parent_id IS NOT NULL, parent_id, name');
    $parents  = [];
    $children = [];
    foreach ($all as $c) {
        if ($c['parent_id']) $children[$c['parent_id']][] = $c;
        else $parents[] = $c;
    }
    return ['parents' => $parents, 'children' => $children];
}

/**
 * Devuelve nombre completo de una campaña: "Padre > Hijo" o solo "Nombre"
 */
function campaignFullName(int $campaignId): string {
    $c = dbFetch('SELECT c.name, p.name AS parent_name FROM campaigns c LEFT JOIN campaigns p ON p.id = c.parent_id WHERE c.id=?', [$campaignId]);
    if (!$c) return '';
    return $c['parent_name'] ? h($c['parent_name']) . ' › ' . h($c['name']) : h($c['name']);
}

function getSolicitudesStats(): array {
    try {
        $pending  = dbFetch('SELECT COUNT(*) AS c FROM solicitudes WHERE status="pending"')['c'] ?? 0;
        $approved = dbFetch('SELECT COUNT(*) AS c FROM solicitudes WHERE status="approved"')['c'] ?? 0;
        $total    = dbFetch('SELECT COUNT(*) AS c FROM solicitudes')['c'] ?? 0;
    } catch (Exception $e) {
        // La tabla solicitudes no existe aún (correr migration_solicitudes.sql)
        $pending = $approved = $total = 0;
    }
    return compact('pending','approved','total');
}

function getServiciosStats(): array {
    try {
        $pending   = dbFetch('SELECT COUNT(*) AS c FROM servicios_solicitudes WHERE status="pending"')['c']   ?? 0;
        $approved  = dbFetch('SELECT COUNT(*) AS c FROM servicios_solicitudes WHERE status="approved"')['c']  ?? 0;
        $completed = dbFetch('SELECT COUNT(*) AS c FROM servicios_solicitudes WHERE status="completed"')['c'] ?? 0;
        $total     = dbFetch('SELECT COUNT(*) AS c FROM servicios_solicitudes')['c']                          ?? 0;
    } catch (Exception $e) {
        $pending = $approved = $completed = $total = 0;
    }
    return compact('pending','approved','completed','total');
}
