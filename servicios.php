<?php
ob_start();
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../mailer.php';
$admin = requireAdmin();
$stats = getStats();

$talleres = [
    'taller1' => '💻 Taller 1 — Tecnología',
    'taller2' => '🔌 Taller 2 — Electrodomésticos',
    'taller3' => '🚗 Taller 3 — Automotores',
];
$urgLabel = ['normal'=>'Normal','urgente'=>'🔴 Urgente','flexible'=>'Flexible'];

$msg = '';

// ── Aprobar ────────────────────────────────────────────────
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    if (!verifyCsrf()) die('Token inválido.');
    $id    = (int)$_GET['approve'];
    $notes = trim($_POST['admin_notes'] ?? '');
    $sol   = dbFetch('SELECT ss.*, u.email, u.name FROM servicios_solicitudes ss JOIN users u ON u.id=ss.user_id WHERE ss.id=?', [$id]);
    if ($sol && $sol['status'] === 'pending') {
        dbUpdate('servicios_solicitudes', [
            'status'      => 'approved',
            'admin_notes' => $notes,
            'reviewed_by' => $admin['id'],
            'reviewed_at' => date('Y-m-d H:i:s'),
        ], 'id=?', [$id]);
        adminLog($admin['id'], 'approve_service', 'servicios_solicitudes', $id);

        // Enviar email
        $mailer = new FlbMailer();
        $solUpd = array_merge($sol, ['admin_notes' => $notes]);
        $sent   = $mailer->send($sol['email'], $sol['name'],
            '✅ Tu solicitud de servicio fue aprobada — Cooperativa FLB',
            FlbMailer::templateServicio($solUpd, 'approved')
        );
        dbUpdate('servicios_solicitudes', ['email_sent' => $sent ? 1 : 0], 'id=?', [$id]);
        $msg = '<div class="flash-success">✅ Solicitud #'.$id.' aprobada' . ($sent ? ' y email enviado.' : ' (email no enviado, revisar SMTP).') . '</div>';
    }
}

// ── Rechazar ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_id'])) {
    if (!verifyCsrf()) die('Token inválido.');
    $id     = (int)$_POST['reject_id'];
    $reason = trim($_POST['rejection_reason'] ?? '');
    $sol    = dbFetch('SELECT ss.*, u.email, u.name FROM servicios_solicitudes ss JOIN users u ON u.id=ss.user_id WHERE ss.id=?', [$id]);
    if ($sol && $sol['status'] === 'pending') {
        dbUpdate('servicios_solicitudes', [
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by'      => $admin['id'],
            'reviewed_at'      => date('Y-m-d H:i:s'),
        ], 'id=?', [$id]);
        adminLog($admin['id'], 'reject_service', 'servicios_solicitudes', $id, $reason);

        $mailer = new FlbMailer();
        $solUpd = array_merge($sol, ['rejection_reason' => $reason]);
        $sent   = $mailer->send($sol['email'], $sol['name'],
            '❌ Tu solicitud de servicio fue rechazada — Cooperativa FLB',
            FlbMailer::templateServicio($solUpd, 'rejected')
        );
        dbUpdate('servicios_solicitudes', ['email_sent' => $sent ? 1 : 0], 'id=?', [$id]);
        $msg = '<div class="flash-danger">Solicitud #'.$id.' rechazada' . ($sent ? ' y email enviado.' : ' (email no enviado).') . '</div>';
    }
}

// ── Marcar completada ──────────────────────────────────────
if (isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    if (!verifyCsrf()) die('Token inválido.');
    $id = (int)$_GET['complete'];
    dbUpdate('servicios_solicitudes', ['status' => 'completed'], 'id=?', [$id]);
    adminLog($admin['id'], 'complete_service', 'servicios_solicitudes', $id);
    $msg = '<div class="flash-success">Solicitud #'.$id.' marcada como completada.</div>';
}

// ── Filtros ────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? 'all';
$filterTaller = $_GET['taller'] ?? 'all';
$where  = ['1=1'];
$params = [];
if ($filterStatus !== 'all') { $where[] = 'ss.status=?'; $params[] = $filterStatus; }
if ($filterTaller !== 'all') { $where[] = 'ss.taller=?'; $params[] = $filterTaller; }

$solicitudes = dbFetchAll(
    'SELECT ss.*, u.name AS client_name, u.email AS client_email,
            a.name AS admin_name
     FROM servicios_solicitudes ss
     JOIN users u ON u.id = ss.user_id
     LEFT JOIN users a ON a.id = ss.reviewed_by
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY FIELD(ss.status,"pending","approved","rejected","completed"), ss.created_at DESC',
    $params
);

// ── Counts ─────────────────────────────────────────────────
try {
    $counts = [
        'all'       => dbFetch('SELECT COUNT(*) AS c FROM servicios_solicitudes')['c'] ?? 0,
        'pending'   => dbFetch('SELECT COUNT(*) AS c FROM servicios_solicitudes WHERE status="pending"')['c'] ?? 0,
        'approved'  => dbFetch('SELECT COUNT(*) AS c FROM servicios_solicitudes WHERE status="approved"')['c'] ?? 0,
        'rejected'  => dbFetch('SELECT COUNT(*) AS c FROM servicios_solicitudes WHERE status="rejected"')['c'] ?? 0,
        'completed' => dbFetch('SELECT COUNT(*) AS c FROM servicios_solicitudes WHERE status="completed"')['c'] ?? 0,
    ];
} catch (Exception $e) { $counts = array_fill_keys(['all','pending','approved','rejected','completed'], 0); }

$csrf = csrfToken();
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
<title>Servicios — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/admin.css">
<style>
.flash-success{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.35);border-radius:10px;padding:.75rem 1.1rem;color:#6EE7B7;margin-bottom:1rem}
.flash-danger{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.35);border-radius:10px;padding:.75rem 1.1rem;color:#FCA5A5;margin-bottom:1rem}
.filter-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem}
.filter-tab{padding:.4rem 1rem;border-radius:50px;font-size:.8rem;font-weight:700;cursor:pointer;text-decoration:none;border:1px solid rgba(21,101,192,.3);color:#BFDBFE;transition:.2s;display:inline-flex;align-items:center;gap:.4rem}
.filter-tab:hover,.filter-tab.active{background:rgba(21,101,192,.2);border-color:#1565C0;color:#60A5FA}
.filter-tab.active.warning{background:rgba(245,158,11,.1);border-color:#D97706;color:#FCD34D}
.badge-count{background:rgba(21,101,192,.3);padding:1px 7px;border-radius:50px;font-size:.72rem}
.srv-row{display:grid;grid-template-columns:auto 1fr auto auto auto;gap:.75rem;align-items:start;padding:1rem 1.1rem;border-bottom:1px solid rgba(21,101,192,.1);transition:background .15s}
.srv-row:hover{background:rgba(21,101,192,.04)}
.srv-row:last-child{border-bottom:none}
.srv-id{color:#4B6A9B;font-size:.78rem;font-weight:600;min-width:28px}
.srv-main{min-width:0}
.srv-client{font-weight:600;font-size:.88rem;color:#EFF6FF}
.srv-taller{font-size:.78rem;color:#BFDBFE;margin-top:.15rem}
.srv-desc{font-size:.8rem;color:#94A3B8;margin-top:.3rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.srv-meta{font-size:.72rem;color:#4B6A9B;margin-top:.3rem;display:flex;gap:.75rem;flex-wrap:wrap}
.srv-urgencia{font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:50px;white-space:nowrap}
.urg-urgente{background:rgba(239,68,68,.15);color:#FCA5A5}
.urg-normal{background:rgba(21,101,192,.12);color:#60A5FA}
.urg-flexible{background:rgba(16,185,129,.1);color:#6EE7B7}
.srv-status-badge{font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:50px;white-space:nowrap}
.status-pending{background:rgba(245,158,11,.12);color:#FCD34D}
.status-approved{background:rgba(16,185,129,.12);color:#6EE7B7}
.status-rejected{background:rgba(239,68,68,.12);color:#FCA5A5}
.status-completed{background:rgba(21,101,192,.12);color:#60A5FA}
.srv-actions{display:flex;flex-direction:column;gap:.4rem;align-items:flex-end;min-width:90px}

/* Modal de aprobación con notas */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(5,13,31,.8);z-index:999;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.modal-overlay.open{display:flex}
.modal-box{background:#080F27;border:1px solid rgba(21,101,192,.35);border-radius:16px;padding:1.75rem;max-width:480px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.5)}
.modal-title{font-family:'Raleway',sans-serif;font-size:1.1rem;font-weight:700;color:#EFF6FF;margin-bottom:1rem}
.modal-input{width:100%;background:rgba(13,26,58,.9);border:1px solid rgba(21,101,192,.3);border-radius:8px;padding:.65rem .9rem;color:#EFF6FF;font-size:.88rem;font-family:'DM Sans',sans-serif;margin-bottom:1rem;outline:none;resize:vertical;min-height:80px}
.modal-input:focus{border-color:#1565C0}
.modal-actions{display:flex;gap:.65rem;justify-content:flex-end}
</style>
</head>
<body class="dark admin-layout">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>

  <div class="admin-content">
    <div class="admin-page-header">
      <div>
        <h1 class="admin-page-title">🔧 Servicios de Taller</h1>
        <p class="admin-page-sub">Gestión de solicitudes de clientes</p>
      </div>
    </div>

    <?= $msg ?>

    <!-- Stats rápidos -->
    <div class="admin-stats-grid" style="margin-bottom:1.5rem">
      <?php foreach ([
        ['🕐','pending',  'Pendientes', $counts['pending'],  'warning'],
        ['✅','approved', 'Aprobadas',  $counts['approved'],  ''],
        ['❌','rejected', 'Rechazadas', $counts['rejected'],  ''],
        ['🏁','completed','Completadas',$counts['completed'], ''],
      ] as [$ico,$st,$lbl,$cnt,$cls]): ?>
      <a href="?status=<?= $st ?>" class="admin-stat-card <?= $cls ?>" style="text-decoration:none">
        <div class="asc-icon"><?= $ico ?></div>
        <div class="asc-val"><?= $cnt ?></div>
        <div class="asc-label"><?= $lbl ?></div>
      </a>
      <?php endforeach ?>
    </div>

    <!-- Filtros -->
    <div class="filter-tabs">
      <?php
      $statusTabs = ['all'=>'Todas','pending'=>'Pendientes','approved'=>'Aprobadas','rejected'=>'Rechazadas','completed'=>'Completadas'];
      foreach ($statusTabs as $val => $lbl):
        $isActive = $filterStatus === $val;
        $isWarn   = $val === 'pending' && $counts['pending'] > 0;
      ?>
      <a href="?status=<?= $val ?>&taller=<?= $filterTaller ?>"
         class="filter-tab <?= $isActive?'active':'' ?> <?= ($isActive&&$isWarn)?'warning':'' ?>">
        <?= $lbl ?><span class="badge-count"><?= $counts[$val] ?></span>
      </a>
      <?php endforeach ?>
      <span style="margin-left:.5rem;color:#4B6A9B;font-size:.75rem">|</span>
      <?php foreach (['all'=>'Todos los talleres']+$talleres as $val => $lbl): ?>
      <a href="?status=<?= $filterStatus ?>&taller=<?= $val ?>"
         class="filter-tab <?= $filterTaller===$val?'active':'' ?>">
        <?= $lbl ?>
      </a>
      <?php endforeach ?>
    </div>

    <!-- Lista -->
    <div class="admin-section-card" style="padding:0;overflow:hidden">
      <?php if (empty($solicitudes)): ?>
        <div class="empty-sm" style="padding:2rem">✅ No hay solicitudes con ese filtro.</div>
      <?php endif ?>

      <?php foreach ($solicitudes as $s):
        $stClass = 'status-' . $s['status'];
        $stLabel = ['pending'=>'Pendiente','approved'=>'Aprobada','rejected'=>'Rechazada','completed'=>'Completada'];
        $urgCls  = 'urg-' . $s['urgencia'];
      ?>
      <div class="srv-row">
        <div class="srv-id">#<?= $s['id'] ?></div>

        <div class="srv-main">
          <div class="srv-client"><?= h($s['client_name']) ?>
            <span style="font-weight:400;font-size:.78rem;color:#4B6A9B"> — <?= h($s['client_email']) ?></span>
          </div>
          <div class="srv-taller"><?= $talleres[$s['taller']] ?? $s['taller'] ?></div>
          <div class="srv-desc"><?= h($s['descripcion']) ?></div>
          <div class="srv-meta">
            <?php if ($s['telefono']): ?><span>📞 <?= h($s['telefono']) ?></span><?php endif ?>
            <?php if ($s['direccion']): ?><span>📍 <?= h($s['direccion']) ?></span><?php endif ?>
            <span>🕐 <?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></span>
            <?php if ($s['reviewed_at']): ?>
              <span>Revisado por <?= h($s['admin_name']??'?') ?> el <?= date('d/m/Y', strtotime($s['reviewed_at'])) ?></span>
            <?php endif ?>
            <?php if ($s['email_sent']): ?><span>📧 Email enviado</span><?php endif ?>
            <?php if ($s['rejection_reason']): ?>
              <span style="color:#FCA5A5">Motivo: <?= h($s['rejection_reason']) ?></span>
            <?php endif ?>
            <?php if ($s['admin_notes']): ?>
              <span style="color:#6EE7B7">Notas: <?= h($s['admin_notes']) ?></span>
            <?php endif ?>
          </div>
        </div>

        <div class="srv-urgencia <?= $urgCls ?>"><?= $urgLabel[$s['urgencia']] ?? $s['urgencia'] ?></div>
        <div class="srv-status-badge <?= $stClass ?>"><?= $stLabel[$s['status']] ?? $s['status'] ?></div>

        <div class="srv-actions">
          <?php if ($s['status'] === 'pending'): ?>
            <button class="btn btn-xs btn-success"
                    onclick="openApprove(<?= $s['id'] ?>, <?= htmlspecialchars(json_encode($s['client_name'])) ?>)">✓ Aprobar</button>
            <button class="btn btn-xs btn-danger"
                    onclick="openReject(<?= $s['id'] ?>, <?= htmlspecialchars(json_encode($s['client_name'])) ?>)">✗ Rechazar</button>
          <?php elseif ($s['status'] === 'approved'): ?>
            <a href="?complete=<?= $s['id'] ?>&_csrf=<?= $csrf ?>"
               class="btn btn-xs btn-outline"
               onclick="return confirm('¿Marcar como completada?')">🏁 Completar</a>
          <?php else: ?>
            <span style="font-size:.72rem;color:#4B6A9B">—</span>
          <?php endif ?>
        </div>
      </div>
      <?php endforeach ?>
    </div>
  </div>
</div>

<!-- Modal Aprobar -->
<div class="modal-overlay" id="modalApprove">
  <div class="modal-box">
    <div class="modal-title">✅ Aprobar solicitud de <span id="approveName"></span></div>
    <p style="font-size:.85rem;color:#BFDBFE;margin-bottom:1rem">
      Se enviará un email al cliente notificando que su solicitud fue aprobada.
    </p>
    <form method="POST" id="approveForm">
      <?= csrfField() ?>
      <input type="hidden" name="_approveId" id="approveId">
      <textarea name="admin_notes" class="modal-input"
                placeholder="Notas para el cliente (opcional): horario, dirección del taller, precio estimado, etc."></textarea>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline-sm" onclick="closeModals()">Cancelar</button>
        <button type="submit" class="btn btn-success" id="approveSubmit">✓ Confirmar aprobación</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Rechazar -->
<div class="modal-overlay" id="modalReject">
  <div class="modal-box">
    <div class="modal-title">✗ Rechazar solicitud de <span id="rejectName"></span></div>
    <p style="font-size:.85rem;color:#BFDBFE;margin-bottom:1rem">
      Se enviará un email al cliente informando el motivo del rechazo.
    </p>
    <form method="POST" id="rejectForm">
      <?= csrfField() ?>
      <input type="hidden" name="reject_id" id="rejectId">
      <textarea name="rejection_reason" class="modal-input"
                placeholder="Motivo del rechazo (se enviará al cliente)..." required></textarea>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline-sm" onclick="closeModals()">Cancelar</button>
        <button type="submit" class="btn btn-danger">✗ Confirmar rechazo</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/app.js"></script>
<script>
function openApprove(id, name) {
  document.getElementById('approveId').value = id;
  document.getElementById('approveName').textContent = name;
  document.getElementById('approveForm').action = '?approve=' + id;
  document.getElementById('modalApprove').classList.add('open');
}
function openReject(id, name) {
  document.getElementById('rejectId').value = id;
  document.getElementById('rejectName').textContent = name;
  document.getElementById('modalReject').classList.add('open');
}
function closeModals() {
  document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
}
document.querySelectorAll('.modal-overlay').forEach(m =>
  m.addEventListener('click', e => { if (e.target === m) closeModals(); })
);
</script>
</body></html>
