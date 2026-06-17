<?php
require_once __DIR__ . '/../functions.php';
$admin = requireAdmin();
$stats = getStats();

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) die('Token inválido.');
    $keys = ['site_subtitle','hero_message','goal_total','donation_amounts','contact_email','smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','smtp_from_name'];
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $val = trim($_POST[$key]);
            $existing = dbFetch('SELECT id FROM settings WHERE setting_key=?', [$key]);
            if ($existing) {
                dbUpdate('settings', ['setting_value' => $val, 'updated_by' => $admin['id']], 'setting_key=?', [$key]);
            } else {
                dbInsert('settings', ['setting_key' => $key, 'setting_value' => $val, 'updated_by' => $admin['id']]);
            }
        }
    }
    adminLog($admin['id'], 'update_settings');
    $success = 'Configuración guardada correctamente.';
}

$settings = [];
foreach (dbFetchAll('SELECT setting_key, setting_value FROM settings') as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
function sv(string $k, array $s, string $def = ''): string {
    return htmlspecialchars($s[$k] ?? $def, ENT_QUOTES|ENT_HTML5, 'UTF-8');
}
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#07050F">
<title>Configuración — Admin</title>
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
      <div><h1 class="admin-page-title">Configuración</h1><p class="admin-page-sub">Textos del sitio, metas y parámetros del sistema</p></div>
    </div>

    <?php if ($success): ?><div class="alert alert-success animate-up"><?= h($success) ?></div><?php endif ?>

    <form method="post">
      <?= csrfField() ?>
      <div class="admin-section-card animate-up">
        <h3 class="asc-section-title">🌐 Textos del Sitio Público</h3>
        <div class="form-group">
          <label class="form-label">Subtítulo del sitio</label>
          <input type="text" name="site_subtitle" class="form-input" value="<?= sv('site_subtitle', $settings) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Mensaje motivacional (hero)</label>
          <textarea name="hero_message" class="form-input" rows="3"><?= sv('hero_message', $settings) ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Email de contacto</label>
          <input type="email" name="contact_email" class="form-input" value="<?= sv('contact_email', $settings) ?>">
        </div>
      </div>

      <div class="admin-section-card animate-up" style="margin-top:1.25rem">
        <h3 class="asc-section-title">💰 Configuración de Donaciones</h3>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Meta total del sitio ($)</label>
            <input type="number" name="goal_total" class="form-input" value="<?= sv('goal_total', $settings, '500000') ?>" min="1">
          </div>
          <div class="form-group">
            <label class="form-label">Montos sugeridos (separados por comas)</label>
            <input type="text" name="donation_amounts" class="form-input" value="<?= sv('donation_amounts', $settings, '500,1000,2000') ?>" placeholder="500,1000,2000">
            <small style="color:var(--text-muted);font-size:.8rem">Ej: 500,1000,2000,5000</small>
          </div>
        </div>
      </div>

      <div class="admin-section-card animate-up" style="margin-top:1.25rem">
        <h3 class="asc-section-title">💳 Datos de MercadoPago</h3>
        <div class="admin-mp-info">
          <div class="mp-row"><span>Public Key:</span><code><?= substr(MP_PUBLIC_KEY, 0, 20) ?>...</code></div>
          <div class="mp-row"><span>Access Token:</span><code><?= substr(MP_ACCESS_TOKEN, 0, 20) ?>...</code></div>
          <div class="mp-row"><span>Modo:</span><code><?= MP_MODE ?></code></div>
        </div>
        <div class="form-notice" style="margin-top:.75rem">
          <span>ℹ️</span> Para cambiar las credenciales de MercadoPago, editá directamente el archivo <code>config.php</code> en el servidor.
        </div>
      </div>

      <div style="margin-top:1.5rem;text-align:right">
    
    <!-- SMTP para notificaciones por email -->
    <div style="margin-top:2rem">
      <h3 style="font-family:'Raleway',sans-serif;font-weight:700;color:#EFF6FF;margin-bottom:1rem;font-size:1rem">
        📧 Configuración de Email (Gmail SMTP)
      </h3>
      <p style="font-size:.82rem;color:#4B6A9B;margin-bottom:1rem">
        Configurá Gmail SMTP para enviar emails automáticos cuando se aprueba o rechaza una solicitud.
        Necesitás activar "Contraseñas de aplicación" en tu cuenta de Google.
      </p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="form-group">
          <label class="form-label">Servidor SMTP</label>
          <input type="text" name="smtp_host" class="form-control" value="<?= sv('smtp_host',$settings,'smtp.gmail.com') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Puerto</label>
          <input type="number" name="smtp_port" class="form-control" value="<?= sv('smtp_port',$settings,'587') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email de envío (Gmail)</label>
          <input type="email" name="smtp_user" class="form-control" value="<?= sv('smtp_user',$settings) ?>" placeholder="tucuenta@gmail.com">
        </div>
        <div class="form-group">
          <label class="form-label">Contraseña de aplicación Google</label>
          <input type="password" name="smtp_pass" class="form-control" value="<?= sv('smtp_pass',$settings) ?>" placeholder="xxxx xxxx xxxx xxxx">
        </div>
        <div class="form-group">
          <label class="form-label">Email remitente (From)</label>
          <input type="email" name="smtp_from" class="form-control" value="<?= sv('smtp_from',$settings) ?>" placeholder="noreply@flb.edu.ar">
        </div>
        <div class="form-group">
          <label class="form-label">Nombre del remitente</label>
          <input type="text" name="smtp_from_name" class="form-control" value="<?= sv('smtp_from_name',$settings,'Cooperativa FLB') ?>">
        </div>
      </div>
      <div style="background:rgba(21,101,192,.08);border:1px solid rgba(21,101,192,.2);border-radius:8px;padding:.75rem 1rem;font-size:.8rem;color:#60A5FA;margin-top:.5rem">
        💡 Para Gmail: activá "Verificación en dos pasos" en tu cuenta, luego andá a
        <strong>Seguridad → Contraseñas de aplicación</strong> y generá una para "Correo / Otro".
        Usá esa contraseña de 16 caracteres en el campo de arriba.
      </div>
    </div>
    <button type="submit" class="btn btn-primary">💾 Guardar Configuración</button>
      </div>
    </form>
  </div>
</div>
<script src="../assets/app.js"></script>
</body></html>
