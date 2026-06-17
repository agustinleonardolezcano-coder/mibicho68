<?php
require_once __DIR__ . '/../functions.php';
$admin = requireAdmin();
$stats = getStats();

// Quick approve via GET
if (!empty($_GET['approve'])) {
    $uid = (int)$_GET['approve'];
    dbUpdate('users', ['status' => 'approved', 'approved_at' => date('Y-m-d H:i:s'), 'approved_by' => $admin['id']], 'id=?', [$uid]);
    adminLog($admin['id'], 'approve_user', 'user', $uid);
    header('Location: users.php?msg=approved'); exit;
}

// Reject via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['reject_user_id'])) {
    if (!verifyCsrf()) { die('Token inválido.'); }
    $uid    = (int)$_POST['reject_user_id'];
    $reason = trim($_POST['reason'] ?? 'Sin motivo especificado.');
    dbUpdate('users', ['status' => 'rejected', 'rejection_reason' => $reason], 'id=?', [$uid]);
    adminLog($admin['id'], 'reject_user', 'user', $uid, $reason);
    header('Location: users.php?msg=rejected'); exit;
}

$filter = $_GET['filter'] ?? 'pending';
$where  = match($filter) {
    'approved' => 'status="approved" AND role="donor"',
    'rejected'  => 'status="rejected"',
    default     => 'status="pending"',
};
$users = dbFetchAll("SELECT * FROM users WHERE $where ORDER BY created_at DESC");
$msgMap = ['approved' => '✅ Usuario aprobado.', 'rejected' => '❌ Usuario rechazado.'];
$rejectTarget = (int)($_GET['reject_modal'] ?? 0);
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#07050F">
<title>Usuarios — Admin</title>
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
      <div><h1 class="admin-page-title">Gestión de Donantes</h1><p class="admin-page-sub">Aprobar o rechazar registros</p></div>
    </div>

    <?php if (!empty($_GET['msg']) && isset($msgMap[$_GET['msg']])): ?>
      <div class="alert alert-success animate-up"><?= $msgMap[$_GET['msg']] ?></div>
    <?php endif ?>

    <!-- Filter tabs -->
    <div class="filter-tabs animate-up">
      <a href="?filter=pending"  class="ftab <?= $filter === 'pending'  ? 'active' : '' ?>">⏳ Pendientes <span class="ftab-count"><?= $stats['pending_users'] ?></span></a>
      <a href="?filter=approved" class="ftab <?= $filter === 'approved' ? 'active' : '' ?>">✅ Aprobados</a>
      <a href="?filter=rejected" class="ftab <?= $filter === 'rejected' ? 'active' : '' ?>">❌ Rechazados</a>
    </div>

    <div class="admin-section-card animate-up" style="animation-delay:.1s">
      <?php if (empty($users)): ?>
        <div class="empty-sm">No hay usuarios en esta categoría.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead><tr>
              <th>Nombre</th><th>Email</th><th>DNI</th><th>Teléfono</th>
              <th>Estado</th><th>Registrado</th><th>Acciones</th>
            </tr></thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <tr>
                <td><strong><?= h($u['name']) ?></strong></td>
                <td><?= h($u['email']) ?></td>
                <td><?= h($u['dni']) ?></td>
                <td><?= h($u['phone'] ?: '—') ?></td>
                <td>
                  <?php if ($u['status'] === 'pending'): ?>
                    <span class="badge badge-warning">Pendiente</span>
                  <?php elseif ($u['status'] === 'approved'): ?>
                    <span class="badge badge-success">Aprobado</span>
                  <?php else: ?>
                    <span class="badge badge-danger" title="<?= h($u['rejection_reason'] ?? '') ?>">Rechazado</span>
                  <?php endif ?>
                </td>
                <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                <td>
                  <?php if ($u['status'] === 'pending'): ?>
                    <a href="?approve=<?= $u['id'] ?>&filter=<?= $filter ?>" class="btn btn-xs btn-success" onclick="return confirm('¿Aprobar a <?= h($u['name']) ?>?')">✓ Aprobar</a>
                    <button class="btn btn-xs btn-danger" onclick="openReject(<?= $u['id'] ?>, '<?= h($u['name']) ?>')">✗ Rechazar</button>
                  <?php elseif ($u['status'] === 'rejected'): ?>
                    <a href="?approve=<?= $u['id'] ?>&filter=<?= $filter ?>" class="btn btn-xs btn-outline" onclick="return confirm('¿Re-aprobar?')">↩ Re-aprobar</a>
                  <?php else: ?>
                    <a href="donations.php?user=<?= $u['id'] ?>" class="btn btn-xs btn-outline">Ver donaciones</a>
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
    <h3 class="modal-title">❌ Rechazar Registro</h3>
    <p class="modal-desc">Ingresá el motivo del rechazo. Este mensaje le será comunicado al usuario.</p>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="reject_user_id" id="rejectUserId">
      <div class="form-group">
        <label class="form-label" id="rejectUserLabel">Usuario</label>
        <textarea name="reason" class="form-input" rows="3" placeholder="Ej: Datos incompletos o incorrectos..." required></textarea>
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
function openReject(id, name) {
  document.getElementById('rejectUserId').value = id;
  document.getElementById('rejectUserLabel').textContent = 'Motivo del rechazo para: ' + name;
  document.getElementById('rejectModal').style.display = 'flex';
}
function closeReject() { document.getElementById('rejectModal').style.display = 'none'; }
<?php if ($rejectTarget): ?>
  const u = <?= json_encode(dbFetch('SELECT id, name FROM users WHERE id=?', [$rejectTarget])) ?>;
  if (u) openReject(u.id, u.name);
<?php endif ?>
</script>
</body></html>
