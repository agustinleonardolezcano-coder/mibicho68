<?php
ob_start();
require_once __DIR__ . '/auth.php';
$token    = trim($_GET['token'] ?? '');
if (!$token) { http_response_code(404); die('No encontrado.'); }

$donation = dbFetch(
    'SELECT d.*, u.name AS donor_name, u.email AS donor_email, u.dni AS donor_dni,
            c.name AS campaign_name, c.parent_id AS camp_parent_id,
            p.name AS parent_campaign_name,
            a.name AS admin_name
     FROM donations d
     JOIN users u ON u.id = d.user_id
     LEFT JOIN campaigns c ON c.id = d.campaign_id
     LEFT JOIN campaigns p ON p.id = c.parent_id
     LEFT JOIN users a ON a.id = d.confirmed_by
     WHERE d.receipt_token=? AND d.status="approved"',
    [$token]
);

if (!$donation) {
    http_response_code(404);
    die('Comprobante no encontrado o aún no aprobado.');
}

// Security: only the donor or admin can see it
$currentUser = getCurrentUser();
if ($currentUser && $currentUser['role'] !== 'admin' && $currentUser['id'] != $donation['user_id']) {
    http_response_code(403); die('Sin acceso.');
}
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Comprobante #<?= $donation['id'] ?> — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Raleway:wght@400;600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
  * { margin:0;padding:0;box-sizing:border-box }
  body { font-family:'DM Sans',sans-serif;background:#f8f5ff;color:#1a0a2e;min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:2rem }
  .receipt { background:#fff;border-radius:16px;box-shadow:0 4px 40px rgba(91,45,142,.15);max-width:600px;width:100%;overflow:hidden }
  .receipt-header { background:linear-gradient(135deg,#2D1B69,#5B2D8E,#7C3AED);color:#fff;padding:2rem 2.5rem;text-align:center }
  .receipt-header img { width:64px;margin-bottom:1rem;filter:brightness(0) invert(1) }
  .receipt-header h1 { font-family:Cinzel,serif;font-size:1.4rem;letter-spacing:.05em }
  .receipt-header p { opacity:.8;font-size:.9rem;margin-top:.25rem }
  .receipt-badge { display:inline-block;background:rgba(255,255,255,.2);border-radius:20px;padding:.25rem 1rem;font-size:.85rem;margin-top:.75rem;border:1px solid rgba(255,255,255,.3) }
  .receipt-body { padding:2rem 2.5rem }
  .receipt-title { font-family:Raleway,sans-serif;font-size:1.1rem;font-weight:700;color:#5B2D8E;margin-bottom:1.25rem;padding-bottom:.75rem;border-bottom:2px solid #f0e8ff }
  .receipt-row { display:flex;justify-content:space-between;align-items:flex-start;padding:.6rem 0;border-bottom:1px solid #f5f0ff }
  .receipt-row:last-child { border-bottom:none }
  .rr-label { color:#7B6A9B;font-size:.9rem }
  .rr-value { font-weight:600;color:#1a0a2e;text-align:right;max-width:60% }
  .receipt-amount { background:linear-gradient(135deg,#f0e8ff,#e8d5ff);border-radius:12px;padding:1.5rem;text-align:center;margin:1.25rem 0 }
  .ra-label { color:#5B2D8E;font-size:.9rem;font-weight:500 }
  .ra-value { font-family:Cinzel,serif;font-size:2.5rem;font-weight:700;color:#2D1B69;margin:.25rem 0 }
  .receipt-footer { background:#f8f5ff;padding:1.25rem 2.5rem;text-align:center;font-size:.8rem;color:#9B8EBA;border-top:1px solid #ede8f5 }
  .receipt-footer .token { font-family:monospace;font-size:.75rem;color:#B39DDB;word-break:break-all }
  .print-btn { display:block;text-align:center;margin:1.25rem 0 0;background:linear-gradient(135deg,#5B2D8E,#7C3AED);color:#fff;padding:.75rem 2rem;border-radius:8px;text-decoration:none;font-weight:600;cursor:pointer;border:none;font-size:1rem;width:100% }
  .back-link { display:block;text-align:center;margin-top:.75rem;color:#7C3AED;text-decoration:none;font-size:.9rem }
  @media print {
    body { background:#fff;padding:0 }
    .receipt { box-shadow:none;border-radius:0;max-width:100% }
    .print-btn, .back-link { display:none }
  }
</style>
</head>
<body>
<div class="receipt">
  <div class="receipt-header">
    <img src="assets/logo.png" alt="FLB">
    <h1><?= SITE_NAME ?></h1>
    <p>Comprobante de Donación</p>
    <div class="receipt-badge">✅ Donación Aprobada</div>
  </div>

  <div class="receipt-body">
    <div class="receipt-title">Detalle de la Donación</div>

    <div class="receipt-amount">
      <div class="ra-label">Monto Donado</div>
      <div class="ra-value"><?= money((float)$donation['amount']) ?></div>
      <div class="ra-label">ARS · <?= date('d/m/Y', strtotime($donation['confirmed_at'])) ?></div>
    </div>

    <div class="receipt-title" style="margin-top:1.25rem">Datos del Donante</div>
    <div class="receipt-row"><div class="rr-label">Nombre</div><div class="rr-value"><?= h($donation['donor_name']) ?></div></div>
    <div class="receipt-row"><div class="rr-label">Email</div><div class="rr-value"><?= h($donation['donor_email']) ?></div></div>
    <div class="receipt-row"><div class="rr-label">DNI</div><div class="rr-value"><?= h($donation['donor_dni']) ?></div></div>

    <div class="receipt-title" style="margin-top:1.25rem">Información del Pago</div>
    <div class="receipt-row"><div class="rr-label">N° Donación</div><div class="rr-value">#<?= str_pad($donation['id'], 6, '0', STR_PAD_LEFT) ?></div></div>
    <div class="receipt-row"><div class="rr-label">Fecha de Pago</div><div class="rr-value"><?= date('d/m/Y H:i', strtotime($donation['created_at'])) ?></div></div>
    <div class="receipt-row"><div class="rr-label">Fecha Aprobación</div><div class="rr-value"><?= date('d/m/Y H:i', strtotime($donation['confirmed_at'])) ?></div></div>
    <div class="receipt-row"><div class="rr-label">Método</div><div class="rr-value"><?= $donation['payment_method'] === 'mercadopago' ? 'MercadoPago' : 'Tarjeta' ?></div></div>
    <?php if ($donation['campaign_name'] && $donation['parent_campaign_name']): ?>
    <div class="receipt-row"><div class="rr-label">Campaña</div><div class="rr-value"><?= h($donation['parent_campaign_name']) ?></div></div>
    <div class="receipt-row"><div class="rr-label">Sub-campaña</div><div class="rr-value"><?= h($donation['campaign_name']) ?></div></div>
    <?php elseif ($donation['campaign_name']): ?>
    <div class="receipt-row"><div class="rr-label">Campaña</div><div class="rr-value"><?= h($donation['campaign_name']) ?></div></div>
    <?php else: ?>
    <div class="receipt-row"><div class="rr-label">Campaña</div><div class="rr-value">Fondo General</div></div>
    <?php endif ?>
    <?php
ob_start(); if ($donation['mp_payment_id']): ?>
    <div class="receipt-row"><div class="rr-label">ID MP</div><div class="rr-value"><?= h($donation['mp_payment_id']) ?></div></div>
    <?php
ob_start(); endif ?>

    <button class="print-btn" onclick="window.print()">🖨️ Imprimir / Guardar como PDF</button>
    <a href="dashboard.php" class="back-link">← Volver a mi panel</a>
  </div>

  <div class="receipt-footer">
    <p><?= SITE_NAME ?> — Comprobante autenticado</p>
    <div class="token">Token: <?= $token ?></div>
  </div>
</div>
</body></html>
