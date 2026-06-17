<?php
ob_start();
require_once __DIR__ . '/functions.php';
$user = requireLogin('/login.php');

$talleres = [
    'taller1' => [
        'icon'  => '💻',
        'nombre'=> 'Taller 1 — Tecnología y Computación',
        'color' => '#06B6D4',
        'items' => [
            'Diagnóstico y reparación de PC, notebook, tablet o celular',
            'Limpieza profunda + cambio pasta térmica + optimización',
            'Instalación de Windows 10/11, Office, antivirus, drivers',
            'Respaldo de fotos, videos, documentos y WhatsApp',
            'Capacitación personalizada (adultos mayores, empleados)',
            'Cambio de disco a SSD, RAM, batería, teclado, pantalla',
            'Armado de PC nueva (gamer, oficina, estudio)',
            'Recuperación de datos borrados, reparación de pendrive/HDD',
            'Asesoramiento para comprar equipo nuevo',
        ],
    ],
    'taller2' => [
        'icon'  => '🔌',
        'nombre'=> 'Taller 2 — Electrodomésticos y Eléctrico',
        'color' => '#F59E0B',
        'items' => [
            'Plancha (no calienta, chispas, cable dañado)',
            'Heladera (no enfría, hace ruido, pierde agua)',
            'Lavarropas (no centrifuga, vibra, error en pantalla)',
            'Microondas, licuadora, batidora, aspiradora, ventilador',
            'Instalación y reparación de LED, tomacorrientes, luces',
            'Termotanque, bomba de agua, aire acondicionado',
            'Instalaciones eléctricas nuevas (cocina, baño, galería)',
            'Diagnóstico de tableros, diferenciales y llaves térmicas',
            'Venta e instalación de repuestos originales',
        ],
    ],
    'taller3' => [
        'icon'  => '🚗',
        'nombre'=> 'Taller 3 — Automotores y Pintura',
        'color' => '#94A3B8',
        'items' => [
            'Service completo: aceite, filtros, correas, bujías, frenos',
            'Reparación de motor, caja, suspensión, dirección, escape',
            'Diagnóstico eléctrico: batería, alternador, centralita, sensores',
            'Pintura: rayones, abolladuras, paragolpes, capot, completa',
            'Cambio de color total o detalles (llantas, spoilers, interior)',
            'Desarme, armado y calibración + prueba en ruta',
            'Venta e instalación de repuestos de calidad',
            'Asesoramiento para vender, comprar o armar tu propio taller',
        ],
    ],
];

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) die('Token inválido.');
    $taller   = $_POST['taller']   ?? '';
    $desc     = trim($_POST['descripcion'] ?? '');
    $nombre   = trim($_POST['nombre_contacto'] ?? $user['name']);
    $telefono = trim($_POST['telefono'] ?? '');
    $dir      = trim($_POST['direccion'] ?? '');
    $urgencia = $_POST['urgencia'] ?? 'normal';

    if (!array_key_exists($taller, $talleres)) $errors[] = 'Seleccioná un taller.';
    if (strlen($desc) < 10) $errors[] = 'La descripción debe tener al menos 10 caracteres.';
    if (!$nombre) $errors[] = 'Ingresá tu nombre de contacto.';
    if (!in_array($urgencia, ['normal','urgente','flexible'])) $urgencia = 'normal';

    if (!$errors) {
        $id = dbInsert('servicios_solicitudes', [
            'user_id'         => $user['id'],
            'taller'          => $taller,
            'descripcion'     => $desc,
            'nombre_contacto' => $nombre,
            'telefono'        => $telefono,
            'direccion'       => $dir,
            'urgencia'        => $urgencia,
            'status'          => 'pending',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        $success = true;
    }
}
?><!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=5.0">
<meta name="theme-color" content="#050D1F">
<title>Solicitar Servicio — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Raleway:wght@300;400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
<style>
.srv-hero{padding:7rem 1.25rem 3rem;text-align:center;background:radial-gradient(ellipse 80% 60% at 50% -20%,rgba(21,101,192,.35),transparent 70%)}
.srv-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;max-width:1000px;margin:0 auto 2.5rem}
.taller-card{border-radius:20px;padding:1.75rem 1.5rem;cursor:pointer;border:2px solid transparent;transition:.3s;position:relative;overflow:hidden;background:rgba(8,15,39,.8)}
.taller-card:hover{transform:translateY(-4px)}
.taller-card.selected{border-color:var(--tc-color)!important;box-shadow:0 0 0 4px rgba(var(--tca-rgb),.15)}
.taller-card input[type=radio]{position:absolute;opacity:0;pointer-events:none}
.tc-icon{font-size:2.5rem;margin-bottom:.75rem}
.tc-name{font-family:'Cinzel',serif;font-size:1rem;font-weight:700;color:#EFF6FF;margin-bottom:.75rem}
.tc-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.35rem}
.tc-list li{font-size:.78rem;color:#BFDBFE;display:flex;gap:.45rem;align-items:flex-start;line-height:1.4}
.tc-list li::before{content:'✓';color:var(--tca);font-size:.75rem;flex-shrink:0;margin-top:.1rem}
.selected-badge{display:none;position:absolute;top:14px;right:14px;background:var(--tca);color:#fff;font-size:.7rem;font-weight:800;padding:.25rem .65rem;border-radius:50px}
.taller-card.selected .selected-badge{display:block}
.form-card{background:rgba(8,15,39,.8);border:1px solid rgba(21,101,192,.25);border-radius:20px;padding:2rem;max-width:700px;margin:0 auto 3rem}
.form-group{margin-bottom:1.25rem}
.form-label{display:block;font-size:.85rem;font-weight:600;color:#BFDBFE;margin-bottom:.5rem}
.form-control{width:100%;background:rgba(13,26,58,.9);border:1px solid rgba(21,101,192,.3);border-radius:10px;padding:.75rem 1rem;color:#EFF6FF;font-size:.92rem;font-family:'DM Sans',sans-serif;transition:.25s;outline:none}
.form-control:focus{border-color:#1565C0;box-shadow:0 0 0 3px rgba(21,101,192,.15)}
textarea.form-control{resize:vertical;min-height:110px}
.urgencia-grid{display:flex;gap:.75rem}
.urgencia-btn{flex:1;padding:.65rem;border:1px solid rgba(21,101,192,.3);border-radius:10px;background:transparent;color:#BFDBFE;font-size:.82rem;cursor:pointer;transition:.25s;text-align:center;font-family:'Raleway',sans-serif;font-weight:600}
.urgencia-btn.active{background:rgba(21,101,192,.2);border-color:#1565C0;color:#60A5FA}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.btn-submit{width:100%;background:linear-gradient(135deg,#0D47A1,#1565C0);color:#fff;padding:1rem;border:none;border-radius:12px;font-family:'Raleway',sans-serif;font-weight:800;font-size:1rem;cursor:pointer;transition:.25s;margin-top:.5rem}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(21,101,192,.5)}
.alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:1rem;color:#FCA5A5;margin-bottom:1.25rem;font-size:.88rem}
.alert-success{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:16px;padding:2rem;text-align:center;max-width:600px;margin:0 auto}
@media(max-width:700px){.srv-grid{grid-template-columns:1fr}.form-row{grid-template-columns:1fr}.urgencia-grid{flex-direction:column}}
</style>
</head>
<body class="dark">

<nav class="navbar navbar-solid">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand">
      <img src="assets/logo.png" alt="FLB" class="nav-logo">
      <span class="brand-text"><span class="brand-main">COOPERATIVA</span><span class="brand-sub">Fray Luis Beltrán</span></span>
    </a>
    <div class="nav-links">
      <a href="index.php" class="nav-link">Inicio</a>
      <a href="dashboard.php" class="btn btn-outline-sm">Mi Panel</a>
      <a href="logout.php" class="nav-link">Salir</a>
    </div>
  </div>
</nav>

<div class="srv-hero">
  <div class="section-tag">Servicios de Taller</div>
  <h1 class="section-title" style="font-family:'Cinzel',serif;font-size:2.2rem;margin:.5rem 0">Solicitá un Servicio</h1>
  <p style="color:#BFDBFE;max-width:580px;margin:.75rem auto 0;line-height:1.75">
    Elegí el taller que necesitás, describí tu problema y el equipo te contactará. 
    Un administrador revisará tu solicitud y recibirás un email con la respuesta.
  </p>
</div>

<div class="container" style="max-width:1100px;margin:0 auto;padding:0 1.25rem">

<?php if ($success): ?>
  <div class="alert-success animate-up">
    <div style="font-size:3rem;margin-bottom:.75rem">✅</div>
    <h2 style="font-family:'Cinzel',serif;color:#EFF6FF;margin-bottom:.5rem">¡Solicitud enviada!</h2>
    <p style="color:#BFDBFE;margin-bottom:1.5rem">Tu solicitud fue registrada. Recibirás un email cuando sea revisada por el administrador.</p>
    <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
      <a href="dashboard.php" class="btn btn-primary">Ver mi panel</a>
      <a href="solicitar-servicio.php" class="btn btn-outline">Nueva solicitud</a>
    </div>
  </div>

<?php else: ?>

<?php if ($errors): ?>
  <div class="alert-error animate-up">
    <?php foreach ($errors as $e): ?><div>⚠ <?= h($e) ?></div><?php endforeach ?>
  </div>
<?php endif ?>

<form method="POST" id="srvForm">
  <?= csrfField() ?>

  <!-- Selección de taller -->
  <div class="section-header" style="text-align:center;margin-bottom:1.5rem">
    <div class="section-tag">Paso 1</div>
    <h2 class="section-title" style="font-size:1.4rem">¿Qué taller necesitás?</h2>
  </div>

  <div class="srv-grid">
    <?php foreach ($talleres as $key => $t): ?>
    <label class="taller-card" id="tc-<?= $key ?>"
           style="--tca:<?= $t['color'] ?>;--tca-rgb:<?= implode(',', sscanf($t['color'],'#%02x%02x%02x')) ?>"
           onclick="selectTaller('<?= $key ?>')">
      <input type="radio" name="taller" value="<?= $key ?>" <?= (($_POST['taller']??'') === $key)?'checked':'' ?>>
      <div class="selected-badge">✓ Seleccionado</div>
      <div class="tc-icon"><?= $t['icon'] ?></div>
      <div class="tc-name"><?= $t['nombre'] ?></div>
      <ul class="tc-list">
        <?php foreach (array_slice($t['items'],0,5) as $item): ?>
          <li><?= h($item) ?></li>
        <?php endforeach ?>
        <?php if(count($t['items'])>5): ?>
          <li style="color:rgba(191,219,254,.5)">+ <?= count($t['items'])-5 ?> servicios más...</li>
        <?php endif ?>
      </ul>
    </label>
    <?php endforeach ?>
  </div>

  <!-- Formulario -->
  <div class="form-card animate-up">
    <div class="section-tag" style="margin-bottom:1rem">Paso 2 — Completá el formulario</div>

    <div class="form-group">
      <label class="form-label">Describí tu problema o lo que necesitás *</label>
      <textarea name="descripcion" class="form-control" placeholder="Ej: Mi notebook no enciende, cuando la enchufo no hace nada. Tiene 3 años y antes andaba bien..." required><?= h($_POST['descripcion']??'') ?></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Nombre de contacto *</label>
        <input type="text" name="nombre_contacto" class="form-control"
               value="<?= h($_POST['nombre_contacto'] ?? $user['name']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Teléfono / WhatsApp</label>
        <input type="tel" name="telefono" class="form-control"
               value="<?= h($_POST['telefono']??'') ?>" placeholder="+54 9 376 XXX XXXX">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Dirección (opcional, para servicios a domicilio)</label>
      <input type="text" name="direccion" class="form-control"
             value="<?= h($_POST['direccion']??'') ?>" placeholder="Calle, número, barrio">
    </div>

    <div class="form-group">
      <label class="form-label">Urgencia</label>
      <div class="urgencia-grid">
        <?php
        $urgSel = $_POST['urgencia'] ?? 'normal';
        foreach (['normal'=>'⏱ Normal (días)','urgente'=>'🔴 Urgente (hoy)','flexible'=>'📅 Flexible (cuando puedan)'] as $val => $lbl):
        ?>
        <button type="button" class="urgencia-btn <?= $urgSel===$val?'active':'' ?>"
                onclick="setUrgencia('<?= $val ?>')"><?= $lbl ?></button>
        <?php endforeach ?>
      </div>
      <input type="hidden" name="urgencia" id="urgencia_val" value="<?= h($urgSel) ?>">
    </div>

    <button type="submit" class="btn-submit">🔧 Enviar Solicitud de Servicio</button>
  </div>

</form>
<?php endif ?>
</div>

<footer class="footer" style="margin-top:3rem">
  <div class="container footer-inner">
    <div class="footer-copy">© <?= date('Y') ?> Cooperativa Fray Luis Beltrán.</div>
  </div>
</footer>

<script>
function selectTaller(key) {
  document.querySelectorAll('.taller-card').forEach(c => c.classList.remove('selected'));
  document.getElementById('tc-' + key).classList.add('selected');
  document.querySelector('input[name=taller][value="'+key+'"]').checked = true;
}
function setUrgencia(val) {
  document.querySelectorAll('.urgencia-btn').forEach(b => b.classList.remove('active'));
  event.target.classList.add('active');
  document.getElementById('urgencia_val').value = val;
}
// Auto-select if already posted
<?php if (!empty($_POST['taller'])): ?>
selectTaller('<?= h($_POST['taller']) ?>');
<?php endif ?>
</script>
</body>
</html>
