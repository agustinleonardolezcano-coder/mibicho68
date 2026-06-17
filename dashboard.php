<?php
ob_start();
require_once __DIR__ . '/functions.php';
$user = requireLogin();

$donations = dbFetchAll(
    'SELECT d.*, c.name AS campaign_name, c.parent_id AS camp_parent_id,
            p.name AS parent_campaign_name
     FROM donations d
     LEFT JOIN campaigns c ON c.id = d.campaign_id
     LEFT JOIN campaigns p ON p.id = c.parent_id
     WHERE d.user_id=? ORDER BY d.created_at DESC',
    [$user['id']]
);

$totalDonated  = array_sum(array_map(fn($d) => $d['status'] === 'approved' ? $d['amount'] : 0, $donations));
$countApproved = count(array_filter($donations, fn($d) => $d['status'] === 'approved'));
$pendingCount  = count(array_filter($donations, fn($d) => $d['status'] === 'pending'));

$statusLabel = ['pending' => 'Pendiente', 'approved' => 'Aprobada', 'rejected' => 'Rechazada', 'refunded' => 'Reembolsada'];

$misSolicitudes = dbFetchAll(
    'SELECT s.*, c.name AS camp_name, c.emoji AS camp_emoji, p.name AS parent_name
     FROM solicitudes s
     LEFT JOIN campaigns c ON c.id = s.campaign_id
     LEFT JOIN campaigns p ON p.id = c.parent_id
     WHERE s.user_id=? ORDER BY s.created_at DESC LIMIT 10',
    [$user['id']]
);
$solPending  = count(array_filter($misSolicitudes, fn($s) => $s['status']==='pending'));
$solApproved = count(array_filter($misSolicitudes, fn($s) => $s['status']==='approved'));
$solStatusLabel = ['pending'=>'Pendiente','approved'=>'Aprobada','rejected'=>'Rechazada'];
$solStatusClass = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger'];
$statusClass = ['pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-danger', 'refunded' => 'badge-info'];

// Servicios de taller
try {
    $misServicios = dbFetchAll(
        'SELECT * FROM servicios_solicitudes WHERE user_id=? ORDER BY created_at DESC LIMIT 8',
        [$user['id']]
    );
} catch (Exception $e) { $misServicios = []; }
$srvPending = count(array_filter($misServicios, fn($s) => $s['status']==='pending'));
$srvTalleres = ['taller1'=>'💻 Tecnología','taller2'=>'🔌 Electrodomésticos','taller3'=>'🚗 Automotores'];
$srvStatusLabel = ['pending'=>'Pendiente','approved'=>'Aprobada','rejected'=>'Rechazada','completed'=>'Completada'];
$srvStatusClass = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger','completed'=>'badge-info'];
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#07050F">
<meta name="apple-mobile-web-app-capable" content="yes">
<title>Mi Panel — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Raleway:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="dark">

<!-- Navbar -->
<nav class="navbar navbar-solid">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand">
      <img src="assets/logo.png" alt="FLB" class="nav-logo">
      <span class="brand-text"><span class="brand-main">COOPERATIVA</span><span class="brand-sub">FLB</span></span>
    </a>
    <div class="nav-links">
      <a href="dashboard.php" class="nav-link active">Mi Panel</a>
      <a href="donate.php" class="btn btn-primary-sm">+ Nueva Donación</a>
      <a href="profile.php" class="nav-link">⚙ Perfil</a>
      <a href="logout.php" class="nav-link">Salir</a>
    </div>
  </div>
</nav>

<main class="page-main">
  <div class="container">

    <!-- Welcome header -->
    <div class="page-header animate-up">
      <div>
        <h1 class="page-title">Hola, <?= h(explode(' ', $user['name'])[0]) ?> 👋</h1>
        <p class="page-subtitle">Tu historial de donaciones y estado de cuenta.</p>
      </div>
      <a href="donate.php" class="btn btn-primary">✦ Donar Ahora</a>
    </div>

    <!-- Stats cards -->
    <div class="dash-stats animate-up" style="animation-delay:.08s">
      <div class="dash-stat-card">
        <div class="ds-icon">💰</div>
        <div class="ds-value counter" data-target="<?= $totalDonated ?>" data-prefix="$" data-format="money">$0</div>
        <div class="ds-label">Total Donado</div>
      </div>
      <div class="dash-stat-card">
        <div class="ds-icon">✅</div>
        <div class="ds-value"><?= $countApproved ?></div>
        <div class="ds-label">Donaciones Aprobadas</div>
      </div>
      <div class="dash-stat-card">
        <div class="ds-icon">⏳</div>
        <div class="ds-value"><?= $pendingCount ?></div>
        <div class="ds-label">Pendientes</div>
      </div>
      <div class="dash-stat-card">
        <div class="ds-icon">📋</div>
        <div class="ds-value"><?= count($donations) ?></div>
        <div class="ds-label">Total Transacciones</div>
      </div>
      <div class="dash-stat-card" style="border-color:rgba(245,158,11,.25);background:rgba(245,158,11,.04)">
        <div class="ds-icon">📬</div>
        <div class="ds-value" style="color:#FCD34D"><?= count($misSolicitudes) ?></div>
        <div class="ds-label">Mis Solicitudes</div>
      </div>
      <div class="ds-card animate-up" style="animation-delay:.25s">
        <div class="ds-icon">🔧</div>
        <div class="ds-value" style="color:#60A5FA"><?= count($misServicios) ?></div>
        <div class="ds-label">Servicios</div>
      </div>
    </div>

    <!-- Pending notice -->
    <?php
ob_start(); if ($pendingCount > 0): ?>
    <div class="alert alert-info animate-up" style="animation-delay:.15s">
      <span>ℹ️</span>
      Tenés <?= $pendingCount ?> donación<?= $pendingCount > 1 ? 'es' : '' ?> pendiente<?= $pendingCount > 1 ? 's' : '' ?> de validación por un administrador.
    </div>
    <?php
ob_start(); endif ?>

    <!-- Donations table -->
    <div class="section-card animate-up" style="animation-delay:.2s">
      <div class="section-card-header">
        <h2>Historial de Donaciones</h2>
        <?php
ob_start(); if (!empty($donations)): ?>
          <a href="donate.php" class="btn btn-sm btn-outline">+ Nueva</a>
        <?php
ob_start(); endif ?>
      </div>

      <?php
ob_start(); if (empty($donations)): ?>
        <div class="empty-state">
          <div class="empty-icon">💙</div>
          <h3>Aún no realizaste ninguna donación</h3>
          <p>¡Sé el primero en contribuir a nuestra institución!</p>
          <a href="donate.php" class="btn btn-primary">Realizar mi primera donación</a>
        </div>
      <?php
ob_start(); else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Monto</th>
                <th>Campaña</th>
                <th>Método</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php
ob_start(); foreach ($donations as $d): ?>
              <tr>
                <td class="text-muted"><?= $d['id'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></td>
                <td class="font-bold text-accent"><?= money((float)$d['amount']) ?></td>
                <td>
                  <?php if ($d['campaign_name']): ?>
                    <?php if ($d['parent_campaign_name']): ?>
                      <span style="font-size:.73rem;color:var(--text-muted)"><?= h($d['parent_campaign_name']) ?> ›</span><br>
                      <span><?= h($d['campaign_name']) ?></span>
                    <?php else: ?>
                      <?= h($d['campaign_name']) ?>
                    <?php endif ?>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif ?>
                </td>
                <td>
                  <?= $d['payment_method'] === 'mercadopago' ? '💳 MercadoPago' : ($d['payment_method'] === 'card' ? '💳 Tarjeta' : '📋 Manual') ?>
                </td>
                <td><span class="badge <?= $statusClass[$d['status']] ?? 'badge-info' ?>"><?= $statusLabel[$d['status']] ?? $d['status'] ?></span></td>
                <td>
                  <?php
ob_start(); if ($d['status'] === 'approved' && $d['receipt_token']): ?>
                    <a href="receipt.php?token=<?= h($d['receipt_token']) ?>" target="_blank" class="btn btn-xs btn-outline" title="Ver comprobante">🧾 Comprobante</a>
                  <?php
ob_start(); else: ?>
                    <span class="text-muted">—</span>
                  <?php
ob_start(); endif ?>
                </td>
              </tr>
              <?php
ob_start(); endforeach ?>
            </tbody>
          </table>
        </div>
      <?php
ob_start(); endif ?>
    </div>

    <!-- Solicitudes section -->
    <div class="section-card animate-up" style="animation-delay:.25s;border-color:rgba(245,158,11,.2);background:linear-gradient(160deg,#0F0B1E,#120e00)">
      <div class="section-card-header">
        <h2 style="color:#FCD34D">📋 Mis Solicitudes</h2>
        <a href="solicitar.php" class="btn btn-sm" style="background:linear-gradient(135deg,#92400E,#D97706);color:#fff;padding:.45rem 1rem;border-radius:8px;font-size:.82rem;font-weight:700;text-decoration:none">+ Nueva Solicitud</a>
      </div>
      <?php if (empty($misSolicitudes)): ?>
        <div class="empty-state" style="padding:2rem">
          <div class="empty-icon">📋</div>
          <h3 style="font-size:1rem">No tenés solicitudes aún</h3>
          <p>Si necesitás uniforme o materiales, podés solicitarlos acá.</p>
          <a href="solicitar.php" class="btn btn-sm" style="background:linear-gradient(135deg,#92400E,#D97706);color:#fff;margin-top:.75rem;display:inline-flex;align-items:center;gap:.4rem">📋 Hacer una Solicitud</a>
        </div>
      <?php else: ?>
        <?php if ($solPending > 0): ?>
        <div class="alert alert-warning" style="margin-bottom:1rem">
          <span>⏳</span> Tenés <?= $solPending ?> solicitud<?= $solPending>1?'es':'' ?> pendiente<?= $solPending>1?'s':'' ?> de revisión.
        </div>
        <?php endif ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead><tr><th>#</th><th>Ítem</th><th>Talle</th><th>Cant.</th><th>Estado</th><th>Fecha</th></tr></thead>
            <tbody>
              <?php foreach ($misSolicitudes as $s): ?>
              <tr>
                <td class="text-muted"><?= $s['id'] ?></td>
                <td>
                  <?php if ($s['parent_name']): ?>
                    <span style="font-size:.72rem;color:var(--text-muted)"><?= h($s['parent_name']) ?> › </span><br>
                  <?php endif ?>
                  <?= h($s['camp_name'] ?? '—') ?>
                </td>
                <td><?= $s['talle'] ? h($s['talle']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $s['cantidad'] ?></td>
                <td><span class="badge <?= $solStatusClass[$s['status']] ?>"><?= $solStatusLabel[$s['status']] ?></span></td>
                <td><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
              </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
        <div style="text-align:right;margin-top:.75rem">
          <a href="solicitar.php" style="font-size:.82rem;color:#D97706">Ver todas / Nueva solicitud →</a>
        </div>
      <?php endif ?>
    </div>


    <!-- Servicios de Taller section -->
    <div class="ds-section" style="margin-top:1.5rem">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
        <h2 style="color:#60A5FA;font-family:'Cinzel',serif;font-size:1.1rem">🔧 Mis Servicios de Taller</h2>
        <a href="solicitar-servicio.php" style="background:linear-gradient(135deg,#0D47A1,#1565C0);color:#fff;padding:.45rem 1rem;border-radius:8px;font-size:.82rem;font-weight:700;text-decoration:none">+ Solicitar</a>
      </div>
      <?php if ($srvPending > 0): ?>
        <div style="background:rgba(21,101,192,.1);border:1px solid rgba(21,101,192,.25);border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.85rem;color:#60A5FA">
          ⏳ Tenés <?= $srvPending ?> servicio<?= $srvPending>1?'s':''?> pendiente<?= $srvPending>1?'s':''?> de revisión.
        </div>
      <?php endif ?>
      <?php if (empty($misServicios)): ?>
        <div style="text-align:center;padding:1.5rem;color:var(--text-muted)">
          <div style="font-size:2rem;margin-bottom:.5rem">🔧</div>
          <p>No tenés servicios solicitados aún.</p>
          <a href="solicitar-servicio.php" style="background:linear-gradient(135deg,#0D47A1,#1565C0);color:#fff;margin-top:.75rem;display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.2rem;border-radius:8px;font-size:.85rem;font-weight:700;text-decoration:none">🔧 Ver Servicios</a>
        </div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:.6rem">
          <?php foreach ($misServicios as $srv): ?>
          <div style="background:rgba(8,15,39,.6);border:1px solid rgba(21,101,192,.15);border-radius:12px;padding:.9rem 1.1rem;display:flex;align-items:flex-start;gap:.85rem">
            <div style="font-size:1.4rem;flex-shrink:0"><?= substr($srvTalleres[$srv['taller']]??'🔧',0,2) ?></div>
            <div style="flex:1;min-width:0">
              <div style="font-size:.88rem;font-weight:600;color:#EFF6FF"><?= $srvTalleres[$srv['taller']] ?? $srv['taller'] ?></div>
              <div style="font-size:.79rem;color:#BFDBFE;margin-top:.15rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($srv['descripcion']) ?></div>
              <div style="font-size:.72rem;color:#4B6A9B;margin-top:.2rem"><?= date('d/m/Y H:i', strtotime($srv['created_at'])) ?></div>
              <?php if ($srv['admin_notes']): ?>
                <div style="font-size:.75rem;color:#6EE7B7;margin-top:.15rem">📝 <?= h($srv['admin_notes']) ?></div>
              <?php endif ?>
              <?php if ($srv['rejection_reason']): ?>
                <div style="font-size:.75rem;color:#FCA5A5;margin-top:.15rem">❌ <?= h($srv['rejection_reason']) ?></div>
              <?php endif ?>
            </div>
            <span class="badge <?= $srvStatusClass[$srv['status']] ?? 'badge-info' ?>" style="white-space:nowrap;flex-shrink:0">
              <?= $srvStatusLabel[$srv['status']] ?? $srv['status'] ?>
            </span>
          </div>
          <?php endforeach ?>
        </div>
        <div style="text-align:right;margin-top:.75rem">
          <a href="solicitar-servicio.php" style="font-size:.82rem;color:#1565C0">Nueva solicitud →</a>
        </div>
      <?php endif ?>
    </div>

  </div>
</main>

<script src="assets/app.js"></script>
</body></html>
