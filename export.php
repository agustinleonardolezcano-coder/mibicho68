<?php
require_once __DIR__ . '/../functions.php';
$admin = requireAdmin();

$from = $_GET['from'] ?? '';
$to   = $_GET['to']   ?? '';
$status = $_GET['status'] ?? 'all';

if (!empty($_GET['download'])) {
    $wheres = [];
    $params = [];
    if ($from) { $wheres[] = 'DATE(d.created_at) >= ?'; $params[] = $from; }
    if ($to)   { $wheres[] = 'DATE(d.created_at) <= ?'; $params[] = $to; }
    if ($status !== 'all') { $wheres[] = 'd.status=?'; $params[] = $status; }
    $whereStr = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

    $rows = dbFetchAll(
        "SELECT d.id, u.name AS donante, u.email, u.dni,
                d.amount, d.status, d.payment_method,
                c.name AS campana, p.name AS campana_padre, d.mp_payment_id,
                d.created_at, d.confirmed_at
         FROM donations d
         JOIN users u ON u.id = d.user_id
         LEFT JOIN campaigns c ON c.id = d.campaign_id
         LEFT JOIN campaigns p ON p.id = c.parent_id
         $whereStr ORDER BY d.created_at DESC",
        $params
    );

    adminLog($admin['id'], 'export_csv', null, null, "Registros: " . count($rows));

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="donaciones_flb_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['ID', 'Donante', 'Email', 'DNI', 'Monto ($)', 'Estado', 'Método', 'Campaña Principal', 'Sub-campaña', 'ID MercadoPago', 'Fecha Creación', 'Fecha Confirmación'], ';');
    foreach ($rows as $r) {
        $campPadre = $r['campana_padre'] ?? ($r['campana'] ? null : null);
        $campHija  = $r['campana_padre'] ? $r['campana'] : null;
        $campPrinc = $r['campana_padre'] ?: ($r['campana'] ?: 'General');
        fputcsv($out, [
            $r['id'], $r['donante'], $r['email'], $r['dni'],
            number_format($r['amount'], 2, ',', '.'),
            $r['status'], $r['payment_method'],
            $campPrinc,
            $campHija ?? '',
            $r['mp_payment_id'] ?? '',
            $r['created_at'], $r['confirmed_at'] ?? '',
        ], ';');
    }
    fclose($out);
    exit;
}

$stats = getStats();
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#07050F">
<title>Exportar — Admin</title>
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
      <div><h1 class="admin-page-title">Exportar Datos</h1><p class="admin-page-sub">Descargá reportes completos en formato CSV</p></div>
    </div>

    <div class="admin-section-card animate-up" style="max-width:560px">
      <h3 style="margin-bottom:1.25rem;color:var(--text-primary)">📥 Reporte de Donaciones</h3>
      <form method="get">
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Desde</label>
            <input type="date" name="from" class="form-input" value="<?= h($from) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Hasta</label>
            <input type="date" name="to" class="form-input" value="<?= h($to) ?>">
          </div>
          <div class="form-group" style="grid-column:span 2">
            <label class="form-label">Estado</label>
            <select name="status" class="form-input form-select">
              <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Todos</option>
              <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Aprobadas</option>
              <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pendientes</option>
              <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rechazadas</option>
            </select>
          </div>
        </div>
        <div class="modal-actions" style="margin-top:1rem">
          <button type="submit" name="preview" value="1" class="btn btn-outline">👁 Vista previa</button>
          <button type="submit" name="download" value="1" class="btn btn-primary">⬇ Descargar CSV</button>
        </div>
      </form>
    </div>

    <?php if (!empty($_GET['preview'])): ?>
    <?php
    $wheres = []; $params2 = [];
    if ($from) { $wheres[] = 'DATE(d.created_at) >= ?'; $params2[] = $from; }
    if ($to)   { $wheres[] = 'DATE(d.created_at) <= ?'; $params2[] = $to; }
    if ($status !== 'all') { $wheres[] = 'd.status=?'; $params2[] = $status; }
    $whereStr2 = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';
    $previewRows = dbFetchAll(
        "SELECT d.id, u.name AS donante, u.email, d.amount, d.status, d.payment_method, c.name AS campana, p.name AS campana_padre, d.created_at
         FROM donations d JOIN users u ON u.id = d.user_id LEFT JOIN campaigns c ON c.id = d.campaign_id LEFT JOIN campaigns p ON p.id = c.parent_id $whereStr2 ORDER BY d.created_at DESC LIMIT 50",
        $params2
    );
    ?>
    <div class="admin-section-card animate-up" style="margin-top:1.25rem">
      <div class="asc-header"><h3>Vista Previa (primeros 50)</h3><span class="badge badge-info"><?= count($previewRows) ?> registros</span></div>
      <div class="table-responsive">
        <table class="data-table">
          <thead><tr><th>#</th><th>Donante</th><th>Email</th><th>Monto</th><th>Estado</th><th>Campaña</th><th>Fecha</th></tr></thead>
          <tbody>
            <?php foreach ($previewRows as $r): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= h($r['donante']) ?></td>
              <td><?= h($r['email']) ?></td>
              <td class="text-accent font-bold"><?= money((float)$r['amount']) ?></td>
              <td><span class="badge <?= ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger'][$r['status']] ?? 'badge-info' ?>"><?= $r['status'] ?></span></td>
              <td>
                <?php if ($r['campana_padre']): ?>
                  <span style="font-size:.73rem;color:var(--text-muted)"><?= h($r['campana_padre']) ?> ›</span><br>
                  <?= h($r['campana']) ?>
                <?php else: ?>
                  <?= h($r['campana'] ?? 'General') ?>
                <?php endif ?>
              </td>
              <td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif ?>
  </div>
</div>
<script src="../assets/app.js"></script>
</body></html>
