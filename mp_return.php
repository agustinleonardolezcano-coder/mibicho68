<?php
ob_start();
require_once __DIR__ . '/../functions.php';
$user = requireLogin();

$mpStatus = $_GET['status'] ?? 'pending';
$donId    = (int)($_GET['did'] ?? 0);
$payId    = $_GET['payment_id'] ?? '';

if ($donId) {
    $donation = dbFetch('SELECT * FROM donations WHERE id=? AND user_id=?', [$donId, $user['id']]);

    if ($donation && $payId) {
        dbUpdate('donations', [
            'mp_payment_id' => $payId,
            'mp_status'     => $mpStatus,
        ], 'id=?', [$donId]);

        // Auto-approve if MP says approved
        if ($mpStatus === 'approved' && $donation['status'] !== 'approved') {
            $sysAdmin = dbFetch('SELECT id FROM users WHERE role="admin" ORDER BY id LIMIT 1');
            approveDonation($donId, $sysAdmin ? (int)$sysAdmin['id'] : 1);
            $mpStatus = 'approved'; // ensure we show correct message
        }
    }
}

$messages = [
    'approved' => ['✅', 'Pago procesado', '¡Tu donación fue aprobada exitosamente! Muchas gracias por tu apoyo a la institución.', 'success'],
    'pending'  => ['⏳', 'Pago en proceso', 'Tu pago está siendo procesado. Te notificaremos cuando se confirme.', 'info'],
    'failure'  => ['❌', 'Pago fallido', 'Hubo un problema con tu pago. Podés intentarlo nuevamente.', 'error'],
];
[$icon, $title, $msg, $type] = $messages[$mpStatus] ?? $messages['pending'];
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $title ?> — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dark auth-page">
<div class="auth-bg"><div class="auth-glow-1"></div><div class="auth-glow-2"></div></div>
<div class="auth-container">
  <div class="auth-card animate-in" style="text-align:center">
    <div style="font-size:4rem;margin-bottom:1rem"><?= $icon ?></div>
    <h1 class="auth-title"><?= $title ?></h1>
    <p class="auth-subtitle"><?= h($msg) ?></p>

    <?php
ob_start(); if ($mpStatus === 'approved' && $donation && $donation['receipt_token']): ?>
      <a href="../receipt.php?token=<?= h($donation['receipt_token']) ?>"
         class="btn btn-primary btn-full" style="margin-top:1.5rem" target="_blank">
        🧾 Ver Comprobante
      </a>
    <?php
ob_start(); endif ?>

    <a href="../dashboard.php" class="btn btn-outline btn-full" style="margin-top:.75rem">
      ← Volver a Mi Panel
    </a>
    <?php
ob_start(); if ($mpStatus === 'failure'): ?>
      <a href="../donate.php" class="btn btn-primary btn-full" style="margin-top:.75rem">
        Reintentar Donación
      </a>
    <?php
ob_start(); endif ?>
  </div>
</div>
</body></html>
