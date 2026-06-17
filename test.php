<?php
// test.php — Página de diagnóstico. ELIMINAR después de usar.
error_reporting(E_ALL);
ini_set('display_errors', '1');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Diagnóstico FLB</title>
<style>
body{font-family:Arial,sans-serif;background:#111;color:#eee;padding:1.5rem;font-size:15px}
h2{color:#A78BFA;border-bottom:1px solid #333;padding-bottom:.5rem}
.ok{color:#10B981}.err{color:#EF4444}.warn{color:#F59E0B}
table{border-collapse:collapse;width:100%;margin-bottom:1.5rem}
td,th{border:1px solid #333;padding:.5rem .75rem;text-align:left;font-size:13px}
th{background:#1a1a2e;color:#A78BFA}
</style>
</head>
<body>
<h2>🔍 Diagnóstico del Sistema</h2>

<h2>PHP</h2>
<table>
<tr><th>Parámetro</th><th>Valor</th></tr>
<tr><td>Versión PHP</td><td><?= phpversion() ?></td></tr>
<tr><td>SAPI</td><td><?= php_sapi_name() ?></td></tr>
<tr><td>PDO disponible</td><td><?= extension_loaded('pdo') ? '<span class="ok">✓ Sí</span>' : '<span class="err">✗ No</span>' ?></td></tr>
<tr><td>PDO MySQL</td><td><?= extension_loaded('pdo_mysql') ? '<span class="ok">✓ Sí</span>' : '<span class="err">✗ No</span>' ?></td></tr>
<tr><td>cURL</td><td><?= extension_loaded('curl') ? '<span class="ok">✓ Sí</span>' : '<span class="err">✗ No</span>' ?></td></tr>
<tr><td>Session</td><td><?= session_status() === PHP_SESSION_NONE ? '<span class="ok">✓ OK</span>' : '<span class="warn">En curso</span>' ?></td></tr>
<tr><td>display_errors</td><td><?= ini_get('display_errors') ?></td></tr>
<tr><td>max_execution_time</td><td><?= ini_get('max_execution_time') ?>s</td></tr>
</table>

<h2>Base de Datos</h2>
<?php
$dbOk = false;
try {
    $pdo = new PDO(
        'mysql:host=sql303.iceiy.com;dbname=icei_41413480_cooperativa_flb;charset=utf8mb4',
        'icei_41413480', 'laurachino007',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
    $dbOk = true;
    echo '<p class="ok">✓ Conexión a MySQL exitosa</p>';
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo '<p>Tablas encontradas: <strong>' . implode(', ', $tables ?: ['(ninguna — ejecutar install.php)']) . '</strong></p>';
} catch (Exception $e) {
    echo '<p class="err">✗ Error de conexión: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>

<h2>Session</h2>
<?php
try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['test'] = 'ok_' . time();
    echo '<p class="ok">✓ Sesiones funcionando. ID: ' . session_id() . '</p>';
} catch (Exception $e) {
    echo '<p class="err">✗ Error de sesión: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>

<h2>Archivos del Sistema</h2>
<?php
$files = ['config.php','db.php','auth.php','functions.php','index.php','login.php',
          'assets/style.css','assets/app.js','assets/logo.png'];
echo '<table><tr><th>Archivo</th><th>Estado</th><th>Tamaño</th></tr>';
foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    $exists = file_exists($path);
    $size = $exists ? number_format(filesize($path)) . ' B' : '—';
    echo '<tr><td>' . $f . '</td><td>' . ($exists ? '<span class="ok">✓ Existe</span>' : '<span class="err">✗ No existe</span>') . '</td><td>' . $size . '</td></tr>';
}
echo '</table>';
?>

<h2>Entorno del Servidor</h2>
<table>
<tr><th>Variable</th><th>Valor</th></tr>
<tr><td>SERVER_SOFTWARE</td><td><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? '—') ?></td></tr>
<tr><td>HTTP_HOST</td><td><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '—') ?></td></tr>
<tr><td>DOCUMENT_ROOT</td><td><?= htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? '—') ?></td></tr>
<tr><td>HTTP_USER_AGENT</td><td><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? '—') ?></td></tr>
</table>

<p style="color:#555;font-size:12px;margin-top:2rem">⚠️ Eliminá este archivo (test.php) del servidor después de revisar.</p>
</body>
</html>
