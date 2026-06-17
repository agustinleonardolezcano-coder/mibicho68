<?php
/**
 * SEEDER — Donaciones de prueba
 * Cooperativa Fray Luis Beltrán
 * USO: seeder.php?key=seed_flb_2026
 * ELIMINAR del servidor después de usarlo.
 */
ob_start();
set_time_limit(300);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$pass = $_GET['key'] ?? '';
if ($pass !== 'seed_flb_2026') {
    die('<p style="font-family:monospace;color:red">Acceso denegado. Usá: seeder.php?key=seed_flb_2026</p>');
}

$totalDeseado = (int)($_GET['total'] ?? 10000);
$totalDeseado = min(max($totalDeseado, 100), 15000);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Seeder — Cooperativa FLB</title>
<style>
  body{font-family:'Courier New',monospace;background:#07050F;color:#EDE9F6;padding:2rem;font-size:14px}
  h2{color:#A78BFA;margin-bottom:1rem}
  .log{background:#0F0B1E;border:1px solid #2D1B69;border-radius:8px;padding:1.25rem;max-height:400px;overflow-y:auto;line-height:1.8}
  .ok{color:#10B981}.warn{color:#F59E0B}.err{color:#EF4444}.acc{color:#C084FC;font-weight:bold}
  .stats{display:flex;gap:2rem;flex-wrap:wrap;margin:1rem 0}
  .stat{background:#0F0B1E;border:1px solid #2D1B69;border-radius:8px;padding:.75rem 1.25rem}
  .stat-val{font-size:1.5rem;color:#C084FC;font-weight:bold}
  .stat-lbl{font-size:.75rem;color:#7B6A9B;margin-top:.2rem}
  a{color:#A78BFA}
</style>
</head>
<body>
<h2>🌱 Seeder — Generando <?= number_format($totalDeseado) ?> donaciones</h2>
<div class="log" id="log">
<?php
flush();

$usuarios = dbFetchAll('SELECT id FROM users WHERE role="donor" AND status="approved"');
/* Todas las campañas (padres e hijas) */
$campanas = dbFetchAll('SELECT id FROM campaigns WHERE status="active"');

if (empty($usuarios)) {
    echo '<span class="err">✗ No hay donantes aprobados. Registrá al menos uno e intentá de nuevo.</span>';
    die('</div></body></html>');
}

$userIds = array_column($usuarios, 'id');
$campIds = array_column($campanas, 'id');
$campIds[] = null; // sin campaña asignada

echo '<span class="ok">✓ Donantes encontrados: ' . count($userIds) . '</span><br>';
echo '<span class="ok">✓ Campañas disponibles: ' . count($campanas) . '</span><br>';
flush();

$montos  = [200,300,500,500,500,1000,1000,1000,2000,2000,2000,3000,5000,500,1000,750,1500,2500,4000,800];
$metodos = ['mercadopago','mercadopago','mercadopago','card','card','manual'];
$estados = ['approved','approved','approved','approved','approved','approved','approved','pending','pending','rejected'];

function randomDate(): string {
    return date('Y-m-d H:i:s', mt_rand(strtotime('-2 years'), time()));
}
function randomToken(): string {
    return bin2hex(random_bytes(16));
}

$lote       = 500;
$insertados = 0;
$errores    = 0;
$inicio     = microtime(true);
$pdo        = getDB();

echo '<span class="ok">✓ Iniciando inserción en lotes de ' . $lote . '...</span><br><br>';
flush();

for ($i = 0; $i < $totalDeseado; $i += $lote) {
    $cantidad = min($lote, $totalDeseado - $i);
    $valores  = [];
    $params   = [];

    for ($j = 0; $j < $cantidad; $j++) {
        $userId      = $userIds[array_rand($userIds)];
        $campId      = $campIds[array_rand($campIds)];
        $monto       = $montos[array_rand($montos)];
        $metodo      = $metodos[array_rand($metodos)];
        $estado      = $estados[array_rand($estados)];
        $createdAt   = randomDate();
        $token       = $estado === 'approved' ? randomToken() : null;
        $confirmedAt = $estado === 'approved' ? $createdAt : null;
        $mpPayId     = ($metodo === 'mercadopago' && $estado === 'approved')
                       ? (string)mt_rand(10000000000, 99999999999) : null;

        $valores[] = '(?,?,?,?,?,?,?,?,?)';
        array_push($params, $userId, $campId, $monto, $estado, $metodo, $token, $mpPayId, $confirmedAt, $createdAt);
    }

    try {
        $sql = 'INSERT INTO donations
                    (user_id, campaign_id, amount, status, payment_method,
                     receipt_token, mp_payment_id, confirmed_at, created_at)
                VALUES ' . implode(',', $valores);
        $pdo->prepare($sql)->execute($params);
        $insertados += $cantidad;
        $pct = round(($insertados / $totalDeseado) * 100);
        echo '<span class="ok">✓ Lote ' . ceil(($i + $lote) / $lote) . ' — ' . number_format($insertados) . '/' . number_format($totalDeseado) . ' (' . $pct . '%)</span><br>';
    } catch (Exception $e) {
        $errores++;
        echo '<span class="err">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
    }
    flush();
}

/* ─── Actualizar current_amount en TODAS las campañas (padres e hijas) ─── */
echo '<br><span class="warn">⏳ Actualizando totales de campañas...</span><br>';
flush();

try {
    $pdo->exec('
        UPDATE campaigns c
        SET current_amount = (
            SELECT COALESCE(SUM(d.amount), 0)
            FROM donations d
            WHERE d.campaign_id = c.id AND d.status = "approved"
        )
    ');
    echo '<span class="ok">✓ Totales directos actualizados.</span><br>';
} catch (Exception $e) {
    echo '<span class="err">✗ ' . htmlspecialchars($e->getMessage()) . '</span><br>';
}

$tiempo    = round(microtime(true) - $inicio, 2);
$total_bd  = dbFetch('SELECT COUNT(*) AS c FROM donations')['c'] ?? 0;
$total_rec = dbFetch('SELECT COALESCE(SUM(amount),0) AS t FROM donations WHERE status="approved"')['t'] ?? 0;

echo '<br><span class="acc">═══════════════════════</span><br>';
echo '<span class="acc">✅ SEEDER COMPLETADO</span><br>';
echo '<span class="acc">═══════════════════════</span><br>';
echo '<span class="ok">Insertados: ' . number_format($insertados) . '</span><br>';
echo '<span class="err">Errores:    ' . $errores . '</span><br>';
echo '<span class="ok">Tiempo:     ' . $tiempo . ' s</span><br>';
echo '<span class="ok">Total en BD: ' . number_format($total_bd) . ' donaciones</span><br>';
echo '<span class="ok">Recaudado: $' . number_format($total_rec, 0, ',', '.') . '</span><br>';
?>
</div>

<div class="stats">
  <div class="stat"><div class="stat-val"><?= number_format($insertados) ?></div><div class="stat-lbl">Insertadas</div></div>
  <div class="stat"><div class="stat-val"><?= $tiempo ?>s</div><div class="stat-lbl">Tiempo</div></div>
  <div class="stat"><div class="stat-val"><?= number_format($total_bd) ?></div><div class="stat-lbl">Total en BD</div></div>
  <div class="stat"><div class="stat-val">$<?= number_format($total_rec, 0, ',', '.') ?></div><div class="stat-lbl">Recaudado</div></div>
</div>

<p style="color:#EF4444;font-weight:bold;margin-top:1.5rem">
  ⚠️ Eliminá <code>seeder.php</code> del servidor una vez que terminaste.
</p>
<p>
  <a href="admin/index.php">→ Dashboard Admin</a> &nbsp;|&nbsp;
  <a href="index.php">→ Ver el sitio</a>
</p>
</body></html>
