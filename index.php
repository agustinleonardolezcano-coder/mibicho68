<?php
ob_start();
require_once __DIR__ . '/../functions.php';
$admin = requireAdmin();

$stats = getStats();
$goal  = (float)getSetting('goal_total', '500000');
$pct   = $goal > 0 ? min(100, round(($stats['total'] / $goal) * 100, 1)) : 0;

$recentDons = dbFetchAll(
    'SELECT d.*, u.name AS donor_name FROM donations d
     JOIN users u ON u.id = d.user_id
     ORDER BY d.created_at DESC LIMIT 10'
);
$pendingUsers = dbFetchAll('SELECT * FROM users WHERE status="pending" ORDER BY created_at');
$recentLogs   = dbFetchAll(
    'SELECT l.*, u.name AS admin_name FROM admin_logs l
     JOIN users u ON u.id = l.admin_id
     ORDER BY l.created_at DESC LIMIT 8'
);
$monthlyData = dbFetchAll(
    'SELECT DATE_FORMAT(confirmed_at,"%Y-%m") AS m, SUM(amount) AS t
     FROM donations WHERE status="approved" AND confirmed_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY m ORDER BY m'
);

/* Campañas (padres + sub-campañas) */
$campStats = dbFetchAll(
    'SELECT c.id, c.name, c.emoji, c.image, c.goal_amount, c.current_amount,
            c.parent_id, p.name AS parent_name, c.status,
            COUNT(d.id) AS don_count,
            COALESCE(SUM(CASE WHEN d.status="approved" THEN d.amount END),0) AS raised
     FROM campaigns c
     LEFT JOIN donations d ON d.campaign_id = c.id
     LEFT JOIN campaigns p ON p.id = c.parent_id
     GROUP BY c.id ORDER BY c.parent_id IS NOT NULL, c.parent_id, c.created_at'
);
$campParents  = array_filter($campStats, fn($c) => !$c['parent_id']);
$campChildren = [];
foreach ($campStats as $c) { if ($c['parent_id']) $campChildren[$c['parent_id']][] = $c; }

// Contador rápido de miembros del equipo
$teamCount = dbFetch('SELECT COUNT(*) AS c FROM team_members')['c'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#07050F">
<title>Admin Dashboard — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Raleway:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/admin.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body class="dark admin-layout">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>

  <div class="admin-content">
    <div class="admin-page-header">
      <div>
        <h1 class="admin-page-title">Dashboard</h1>
        <p class="admin-page-sub">Resumen general del sistema</p>
      </div>
      <div class="header-actions">
        <a href="export.php" class="btn btn-outline-sm">⬇ Exportar CSV</a>
        <a href="team.php" class="btn btn-outline-sm">👥 Gestionar Equipo</a>
        <a href="campaigns.php" class="btn btn-primary-sm">+ Campaña</a>
      </div>
    </div>

    <!-- Stats principales -->
    <div class="admin-stats-grid">
      <div class="admin-stat-card purple">
        <div class="asc-icon">💰</div>
        <div class="asc-val counter" data-target="<?= $stats['total'] ?>" data-prefix="$" data-format="money">$0</div>
        <div class="asc-label">Total Recaudado</div>
        <div class="asc-sub"><?= $pct ?>% de la meta</div>
      </div>
      <div class="admin-stat-card">
        <div class="asc-icon">🙌</div>
        <div class="asc-val counter" data-target="<?= $stats['donors'] ?>">0</div>
        <div class="asc-label">Donantes Activos</div>
      </div>
      <div class="admin-stat-card">
        <div class="asc-icon">📅</div>
        <div class="asc-val counter" data-target="<?= $stats['month'] ?>" data-prefix="$" data-format="money">$0</div>
        <div class="asc-label">Donado este Mes</div>
      </div>
      <div class="admin-stat-card">
        <div class="asc-icon">🎯</div>
        <div class="asc-val"><?= $stats['camps'] ?></div>
        <div class="asc-label">Campañas Activas</div>
      </div>
      <div class="admin-stat-card <?= $stats['pending_users'] > 0 ? 'warning' : '' ?>">
        <div class="asc-icon">👤</div>
        <div class="asc-val"><?= $stats['pending_users'] ?></div>
        <div class="asc-label">Registros Pendientes</div>
        <?php if ($stats['pending_users']): ?>
          <a href="users.php" class="asc-action">Revisar →</a>
        <?php endif ?>
      </div>
      <div class="admin-stat-card <?= $stats['pending_dons'] > 0 ? 'warning' : '' ?>">
        <div class="asc-icon">⏳</div>
        <div class="asc-val"><?= $stats['pending_dons'] ?></div>
        <div class="asc-label">Donaciones Pendientes</div>
        <?php if ($stats['pending_dons']): ?>
          <a href="donations.php" class="asc-action">Revisar →</a>
        <?php endif ?>
      </div>
    </div>

    <!-- Barra de progreso global -->
    <div class="admin-section-card animate-up" style="animation-delay:.1s">
      <div class="asc-header"><h3>Progreso de Meta Global</h3><span class="badge badge-success"><?= $pct ?>%</span></div>
      <div class="progress-bar-wrap" style="margin:.75rem 0">
        <div class="progress-bar-fill" style="--pct:<?= $pct ?>%"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:.85rem;color:var(--text-muted)">
        <span>Recaudado: <?= money($stats['total']) ?></span>
        <span>Meta: <?= money($goal) ?></span>
      </div>
    </div>

    <div class="admin-two-col">
      <!-- Gráfico últimos 6 meses -->
      <div class="admin-section-card animate-up" style="animation-delay:.15s">
        <div class="asc-header"><h3>Recaudación Últimos 6 Meses</h3></div>
        <canvas id="monthChart" height="200"></canvas>
      </div>

      <!-- Registros pendientes -->
      <div class="admin-section-card animate-up" style="animation-delay:.2s">
        <div class="asc-header">
          <h3>Registros Pendientes</h3>
          <a href="users.php" class="link-accent" style="font-size:.85rem">Ver todos →</a>
        </div>
        <?php if (empty($pendingUsers)): ?>
          <div class="empty-sm">✅ No hay registros pendientes</div>
        <?php else: ?>
          <?php foreach ($pendingUsers as $pu): ?>
          <div class="pending-user-row">
            <div class="pur-info">
              <strong><?= h($pu['name']) ?></strong>
              <span><?= h($pu['email']) ?> · DNI: <?= h($pu['dni']) ?></span>
              <span class="text-muted"><?= date('d/m/Y H:i', strtotime($pu['created_at'])) ?></span>
            </div>
            <div class="pur-actions">
              <a href="users.php?approve=<?= $pu['id'] ?>" class="btn btn-xs btn-success" onclick="return confirm('Aprobar a <?= h($pu['name']) ?>?')">✓</a>
              <a href="users.php?reject_modal=<?= $pu['id'] ?>" class="btn btn-xs btn-danger">✗</a>
            </div>
          </div>
          <?php endforeach ?>
        <?php endif ?>
      </div>
    </div>

    <!-- Donaciones recientes -->
    <div class="admin-section-card animate-up" style="animation-delay:.25s">
      <div class="asc-header">
        <h3>Donaciones Recientes</h3>
        <a href="donations.php" class="link-accent" style="font-size:.85rem">Ver todas →</a>
      </div>
      <div class="table-responsive">
        <table class="data-table">
          <thead><tr><th>#</th><th>Donante</th><th>Monto</th><th>Método</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php
            $sl = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger','refunded'=>'badge-info'];
            $ll = ['pending'=>'Pendiente','approved'=>'Aprobada','rejected'=>'Rechazada','refunded'=>'Reembolsada'];
            foreach ($recentDons as $d): ?>
            <tr>
              <td class="text-muted"><?= $d['id'] ?></td>
              <td><?= h($d['donor_name']) ?></td>
              <td class="font-bold text-accent"><?= money((float)$d['amount']) ?></td>
              <td><?= $d['payment_method'] === 'mercadopago' ? 'MP' : 'Tarjeta' ?></td>
              <td><span class="badge <?= $sl[$d['status']] ?? 'badge-info' ?>"><?= $ll[$d['status']] ?? $d['status'] ?></span></td>
              <td><?= date('d/m/Y', strtotime($d['created_at'])) ?></td>
              <td>
                <?php if ($d['status'] === 'pending'): ?>
                  <a href="donations.php?approve=<?= $d['id'] ?>" class="btn btn-xs btn-success" onclick="return confirm('¿Aprobar?')">✓</a>
                  <a href="donations.php?reject_modal=<?= $d['id'] ?>" class="btn btn-xs btn-danger">✗</a>
                <?php elseif ($d['status'] === 'approved' && $d['receipt_token']): ?>
                  <a href="../receipt.php?token=<?= h($d['receipt_token']) ?>" target="_blank" class="btn btn-xs btn-outline">🧾</a>
                <?php else: ?>—<?php endif ?>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Widget de Campañas (se mantiene igual) -->
    <!-- ... (tu código de campañas se mantiene exactamente igual) ... -->

    <!-- Activity log -->
    <div class="admin-section-card animate-up" style="animation-delay:.3s">
      <div class="asc-header"><h3>Actividad Reciente</h3></div>
      <?php foreach ($recentLogs as $log): ?>
      <div class="log-row">
        <div class="log-icon">⚡</div>
        <div class="log-info">
          <strong><?= h($log['admin_name']) ?></strong>
          <span><?= h($log['action']) ?><?= $log['details'] ? ': ' . h($log['details']) : '' ?></span>
        </div>
        <div class="log-time"><?= date('d/m H:i', strtotime($log['created_at'])) ?></div>
      </div>
      <?php endforeach ?>
    </div>

  </div>
</div>

<script src="../assets/app.js"></script>
<script>
/* Gráfico de los últimos 6 meses */
const months = <?= json_encode(array_column($monthlyData, 'm')) ?>;
const totals = <?= json_encode(array_map(fn($r) => (float)$r['t'], $monthlyData)) ?>;
const ctx = document.getElementById('monthChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: months.map(m => {
      const [y, mo] = m.split('-');
      return ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][parseInt(mo)-1] + ' ' + y;
    }),
    datasets: [{
      label: 'Recaudación ARS',
      data: totals,
      backgroundColor: 'rgba(21,101,192,.5)',
      borderColor: '#1565C0',
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { ticks: { color: '#9B8EBA', callback: v => '$' + v.toLocaleString('es-AR') }, grid: { color: 'rgba(255,255,255,.05)' } },
      x: { ticks: { color: '#9B8EBA' }, grid: { display: false } }
    }
  }
});

function cdToggle(id) {
  document.getElementById('cdrow-' + id)?.classList.toggle('open');
}
</script>
</body>
</html>