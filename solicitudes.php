<?php
require_once __DIR__ . '/../functions.php';
$admin = requireAdmin();
$stats = getStats();

/* ─── Aprobar ─── */
if (!empty($_GET['approve'])) {
    $sid = (int)$_GET['approve'];
    dbUpdate('solicitudes', [
        'status'      => 'approved',
        'reviewed_by' => $admin['id'],
        'reviewed_at' => date('Y-m-d H:i:s'),
    ], 'id=?', [$sid]);
    adminLog($admin['id'], 'approve_solicitud', 'solicitud', $sid);
    header('Location: solicitudes.php?msg=approved'); exit;
}

/* ─── Rechazar ─── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['reject_sol_id'])) {
    if (!verifyCsrf()) die('Token inválido.');
    $sid    = (int)$_POST['reject_sol_id'];
    $reason = trim($_POST['reason'] ?? 'Sin motivo.');
    dbUpdate('solicitudes', [
        'status'           => 'rejected',
        'rejection_reason' => $reason,
        'reviewed_by'      => $admin['id'],
        'reviewed_at'      => date('Y-m-d H:i:s'),
    ], 'id=?', [$sid]);
    adminLog($admin['id'], 'reject_solicitud', 'solicitud', $sid, $reason);
    header('Location: solicitudes.php?msg=rejected'); exit;
}

$filterStatus = $_GET['status'] ?? 'all';
$whereArr = []; $params = [];
if ($filterStatus !== 'all') { $whereArr[] = 's.status=?'; $params[] = $filterStatus; }
$whereStr = $whereArr ? 'WHERE ' . implode(' AND ', $whereArr) : '';

$solicitudes = dbFetchAll(
    "SELECT s.*, u.name AS donor_name, u.email AS donor_email,
            c.name AS camp_name, c.emoji AS camp_emoji, c.image AS camp_image,
            p.name AS parent_name,
            a.name AS reviewer_name
     FROM solicitudes s
     JOIN users u ON u.id = s.user_id
     LEFT JOIN campaigns c ON c.id = s.campaign_id
     LEFT JOIN campaigns p ON p.id = c.parent_id
     LEFT JOIN users a ON a.id = s.reviewed_by
     $whereStr
     ORDER BY s.created_at DESC LIMIT 300",
    $params
);

$solStats = getSolicitudesStats();
$statusLabel = ['pending'=>'Pendiente','approved'=>'Aprobada','rejected'=>'Rechazada'];
$statusClass = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger'];
$rejectTarget = (int)($_GET['reject_modal'] ?? 0);
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="theme-color" content="#07050F">
<title>Solicitudes — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/admin.css">
<style>
:root { --gold:#D97706; --gold-bright:#F59E0B; --gold-glow:rgba(245,158,11,.2); }
.sol-summary{display:flex;flex-wrap:wrap;gap:.85rem;margin-bottom:1.5rem}
.sol-sum-card{flex:1 1 140px;background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.2);border-radius:12px;padding:1rem;text-align:center}
.sol-sum-val{font-family:'Cinzel',serif;font-size:1.6rem;color:#FCD34D;font-weight:700}
.sol-sum-lbl{font-size:.75rem;color:#A0916E;margin-top:.2rem}
.sol-row{display:flex;align-items:flex-start;gap:.9rem;padding:.85rem 1rem;border-radius:12px;border:1px solid transparent;transition:.2s;margin-bottom:.5rem}
.sol-row:hover{background:rgba(255,255,255,.02);border-color:rgba(245,158,11,.12)}
.sol-item-img{width:44px;height:44px;border-radius:9px;object-fit:cover;flex-shrink:0}
.sol-item-emoji{width:44px;height:44px;border-radius:9px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
.sol-info{flex:1;min-width:0}
.sol-name{font-weight:700;font-size:.9rem}
.sol-meta{font-size:.78rem;color:var(--text-muted);margin-top:.2rem;line-height:1.5}
.sol-actions{display:flex;gap:.4rem;flex-shrink:0;align-items:center;flex-wrap:wrap}
</style>
</head>
<body class="dark admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="admin-content">

    <div class="admin-page-header">
      <div><h1 class="admin-page-title">Solicitudes</h1><p class="admin-page-sub">Gestión de pedidos de uniformes y materiales</p></div>
    </div>

    <?php if (!empty($_GET['msg'])): ?>
      <div class="alert alert-<?= $_GET['msg']==='approved'?'success':'error' ?> animate-up">
        <?= $_GET['msg']==='approved' ? '✅ Solicitud aprobada.' : '❌ Solicitud rechazada.' ?>
      </div>
    <?php endif ?>

    <!-- Resumen -->
    <div class="sol-summary animate-up">
      <div class="sol-sum-card">
        <div class="sol-sum-val"><?= $solStats['total'] ?></div>
        <div class="sol-sum-lbl">Total</div>
      </div>
      <div class="sol-sum-card" style="background:rgba(245,158,11,.12)">
        <div class="sol-sum-val" style="color:#FCD34D"><?= $solStats['pending'] ?></div>
        <div class="sol-sum-lbl">Pendientes</div>
      </div>
      <div class="sol-sum-card" style="background:rgba(16,185,129,.07);border-color:rgba(16,185,129,.2)">
        <div class="sol-sum-val" style="color:#6EE7B7"><?= $solStats['approved'] ?></div>
        <div class="sol-sum-lbl">Aprobadas</div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filter-tabs animate-up">
      <a href="?status=all"      class="ftab <?= $filterStatus==='all'     ?'active':'' ?>">📋 Todas</a>
      <a href="?status=pending"  class="ftab <?= $filterStatus==='pending' ?'active':'' ?>">⏳ Pendientes <span class="ftab-count"><?= $solStats['pending'] ?></span></a>
      <a href="?status=approved" class="ftab <?= $filterStatus==='approved'?'active':'' ?>">✅ Aprobadas</a>
      <a href="?status=rejected" class="ftab <?= $filterStatus==='rejected'?'active':'' ?>">❌ Rechazadas</a>
    </div>

    <div class="admin-section-card animate-up" style="animation-delay:.1s">
      <?php if (empty($solicitudes)): ?>
        <div class="empty-sm">No hay solicitudes en esta categoría.</div>
      <?php else: ?>
        <?php foreach ($solicitudes as $s): ?>
        <div class="sol-row">
          <?php if ($s['camp_image']): ?>
            <img src="../<?= h($s['camp_image']) ?>" alt="" class="sol-item-img">
          <?php else: ?>
            <div class="sol-item-emoji"><?= h($s['camp_emoji'] ?? '📋') ?></div>
          <?php endif ?>
          <div class="sol-info">
            <div class="sol-name">
              <?= h($s['donor_name']) ?>
              <span class="badge <?= $statusClass[$s['status']] ?>" style="margin-left:.35rem"><?= $statusLabel[$s['status']] ?></span>
            </div>
            <div class="sol-meta">
              DNI: <strong><?= h($s['dni']) ?></strong> ·
              <?php if ($s['parent_name']): ?><?= h($s['parent_name']) ?> › <?php endif ?>
              <strong><?= h($s['camp_name'] ?? 'Ítem general') ?></strong>
              <?php if ($s['talle']): ?> · Talle <?= h($s['talle']) ?><?php endif ?>
              · Cant. <?= $s['cantidad'] ?> ·
              <?= date('d/m/Y H:i', strtotime($s['created_at'])) ?>
              <?php if ($s['notes']): ?>
                <br>📝 <?= h($s['notes']) ?>
              <?php endif ?>
              <?php if ($s['rejection_reason']): ?>
                <br><span style="color:#FCA5A5">Motivo rechazo: <?= h($s['rejection_reason']) ?></span>
              <?php endif ?>
              <?php if ($s['reviewer_name'] && $s['reviewed_at']): ?>
                <br><span style="color:#6EE7B7">Revisada por <?= h($s['reviewer_name']) ?> el <?= date('d/m/Y', strtotime($s['reviewed_at'])) ?></span>
              <?php endif ?>
            </div>
          </div>
          <div class="sol-actions">
            <?php if ($s['status']==='pending'): ?>
              <a href="?approve=<?= $s['id'] ?>&status=<?= $filterStatus ?>" class="btn btn-xs btn-success" onclick="return confirm('¿Aprobar solicitud de <?= h($s['donor_name']) ?>?')">✓ Aprobar</a>
              <button class="btn btn-xs btn-danger" onclick="openReject(<?= $s['id'] ?>)">✗ Rechazar</button>
            <?php else: ?>
              <span style="font-size:.75rem;color:var(--text-muted)">#<?= $s['id'] ?></span>
            <?php endif ?>
          </div>
        </div>
        <?php endforeach ?>
      <?php endif ?>
    </div>

  </div>
</div>

<!-- Modal rechazo -->
<div class="modal-overlay" id="rejectModal" style="display:none" onclick="if(event.target===this)closeReject()">
  <div class="modal-box">
    <h3 class="modal-title">❌ Rechazar Solicitud</h3>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="reject_sol_id" id="rejectSolId">
      <div class="form-group">
        <label class="form-label">Motivo del rechazo</label>
        <textarea name="reason" class="form-input" rows="3" placeholder="Ej: Talle no disponible, solicitud duplicada..." required></textarea>
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
function openReject(id){document.getElementById('rejectSolId').value=id;document.getElementById('rejectModal').style.display='flex';}
function closeReject(){document.getElementById('rejectModal').style.display='none';}
<?php if ($rejectTarget): ?>openReject(<?= $rejectTarget ?>);<?php endif ?>
</script>
</body></html>
