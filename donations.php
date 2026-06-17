<?php
require_once __DIR__ . '/../functions.php';
$admin = requireAdmin();
$stats = getStats();

// Approve
if (!empty($_GET['approve'])) {
    $did = (int)$_GET['approve'];
    approveDonation($did, $admin['id']);
    header('Location: donations.php?msg=approved'); exit;
}

// Reject via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['reject_donation_id'])) {
    if (!verifyCsrf()) die('Token inválido.');
    $did    = (int)$_POST['reject_donation_id'];
    $reason = trim($_POST['reason'] ?? 'Sin motivo.');
    rejectDonation($did, $admin['id'], $reason);
    header('Location: donations.php?msg=rejected'); exit;
}

$filterStatus = $_GET['status'] ?? 'all';
$filterUser   = (int)($_GET['user'] ?? 0);
$whereArr = [];
$params   = [];
if ($filterStatus !== 'all') { $whereArr[] = 'd.status=?'; $params[] = $filterStatus; }
if ($filterUser)              { $whereArr[] = 'd.user_id=?'; $params[] = $filterUser; }
$whereStr = $whereArr ? 'WHERE ' . implode(' AND ', $whereArr) : '';

$donations = dbFetchAll(
    "SELECT d.*, u.name AS donor_name, u.email AS donor_email,
            c.name AS campaign_name, c.parent_id AS camp_parent_id,
            p.name AS parent_campaign_name
     FROM donations d
     JOIN users u ON u.id = d.user_id
     LEFT JOIN campaigns c ON c.id = d.campaign_id
     LEFT JOIN campaigns p ON p.id = c.parent_id
     $whereStr
     ORDER BY d.created_at DESC LIMIT 200",
    $params
);

$statusLabel = ['pending'=>'Pendiente','approved'=>'Aprobada','rejected'=>'Rechazada','refunded'=>'Reembolsada'];
$statusClass = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger','refunded'=>'badge-info'];
$rejectTarget = (int)($_GET['reject_modal'] ?? 0);
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#07050F">
<title>Donaciones — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="dark admin-layout">
<?php
include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-main">
  <?php
include __DIR__ . '/partials/topbar.php'; ?>
  <div class="admin-content">
    <div class="admin-page-header">
      <div><h1 class="admin-page-title">Donaciones</h1><p class="admin-page-sub">Validación y gestión de pagos</p></div>
      <a href="export.php" class="btn btn-outline-sm">⬇ Exportar CSV</a>
    </div>

    <?php if (!empty($_GET['msg'])): ?>
      <div class="alert alert-<?= $_GET['msg'] === 'approved' ? 'success' : 'error' ?> animate-up">
        <?= $_GET['msg'] === 'approved' ? '✅ Donación aprobada correctamente.' : '❌ Donación rechazada.' ?>
      </div>
    <?php endif ?>

    <!-- Filters -->
    <div class="filter-tabs animate-up">
      <a href="?status=all"      class="ftab <?= $filterStatus === 'all'      ? 'active':'' ?>">📋 Todas</a>
      <a href="?status=pending"  class="ftab <?= $filterStatus === 'pending'  ? 'active':'' ?>">⏳ Pendientes <span class="ftab-count"><?= $stats['pending_dons'] ?></span></a>
      <a href="?status=approved" class="ftab <?= $filterStatus === 'approved' ? 'active':'' ?>">✅ Aprobadas</a>
      <a href="?status=rejected" class="ftab <?= $filterStatus === 'rejected' ? 'active':'' ?>">❌ Rechazadas</a>
    </div>

    <div class="admin-section-card animate-up" style="animation-delay:.1s">
      <?php if (empty($donations)): ?>
        <div class="empty-sm">No hay donaciones en esta categoría.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead><tr>
              <th>#</th><th>Donante</th><th>Monto</th><th>Campaña</th>
              <th>Método</th><th>Estado</th><th>Fecha</th><th>Acciones</th>
            </tr></thead>
            <tbody>
              <?php foreach ($donations as $d): ?>
              <tr>
                <td class="text-muted"><?= $d['id'] ?></td>
                <td>
                  <div><?= h($d['donor_name']) ?></div>
                  <div class="text-muted" style="font-size:.8rem"><?= h($d['donor_email']) ?></div>
                </td>
                <td class="font-bold text-accent"><?= money((float)$d['amount']) ?></td>
                <td>
                  <?php if ($d['campaign_name']): ?>
                    <?php if ($d['parent_campaign_name']): ?>
                      <span style="font-size:.75rem;color:var(--text-muted)"><?= h($d['parent_campaign_name']) ?> ›</span><br>
                      <span style="font-size:.85rem"><?= h($d['campaign_name']) ?></span>
                    <?php else: ?>
                      <?= h($d['campaign_name']) ?>
                    <?php endif ?>
                  <?php else: ?>
                    <span class="text-muted">General</span>
                  <?php endif ?>
                </td>
                <td><?= $d['payment_method'] === 'mercadopago' ? '💳 MP' : '💳 Tarjeta' ?></td>
                <td><span class="badge <?= $statusClass[$d['status']] ?>"><?= $statusLabel[$d['status']] ?></span></td>
                <td><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></td>
                <td>
                  <?php if ($d['status'] === 'pending'): ?>
                    <a href="?approve=<?= $d['id'] ?>&status=<?= $filterStatus ?>" class="btn btn-xs btn-success" onclick="return confirm('¿Aprobar esta donación?')">✓</a>
                    <button class="btn btn-xs btn-danger" onclick="openReject(<?= $d['id'] ?>)">✗</button>
                  <?php elseif ($d['status'] === 'approved' && $d['receipt_token']): ?>
                    <a href="../receipt.php?token=<?= h($d['receipt_token']) ?>" target="_blank" class="btn btn-xs btn-outline">🧾</a>
                  <?php else: ?>—<?php endif ?>
                  <?php if ($d['mp_payment_id']): ?>
                    <span class="text-muted" style="font-size:.75rem;display:block">MP:<?= h($d['mp_payment_id']) ?></span>
                  <?php endif ?>
                </td>
              </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endif ?>
    </div>
  </div>
</div>

<!-- Reject modal -->
<div class="modal-overlay" id="rejectModal" style="display:none" onclick="if(event.target===this)closeReject()">
  <div class="modal-box">
    <h3 class="modal-title">❌ Rechazar Donación</h3>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="reject_donation_id" id="rejectDonId">
      <div class="form-group">
        <label class="form-label">Motivo del rechazo</label>
        <textarea name="reason" class="form-input" rows="3" placeholder="Ej: Pago no procesado correctamente..." required></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeReject()">Cancelar</button>
        <button type="submit" class="btn btn-danger">Confirmar Rechazo</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/app.js"></script>
<script>
function openReject(id) {
  document.getElementById('rejectDonId').value = id;
  document.getElementById('rejectModal').style.display = 'flex';
}
function closeReject() { document.getElementById('rejectModal').style.display = 'none'; }
<?php if ($rejectTarget): ?>openReject(<?= $rejectTarget ?>);<?php endif ?>
</script>
</body></html>
