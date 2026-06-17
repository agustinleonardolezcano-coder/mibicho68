<?php
ob_start();
require_once __DIR__ . '/functions.php';
$user = requireLogin();

/* ─── Opciones disponibles (sub-campañas de uniforme, o cualquier activa) ─── */
$campaigns = dbFetchAll(
    'SELECT c.*, p.name AS parent_name FROM campaigns c
     LEFT JOIN campaigns p ON p.id = c.parent_id
     WHERE c.status="active" ORDER BY c.parent_id IS NOT NULL, c.parent_id, c.name'
);

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) die('Token inválido.');

    $campId   = (int)($_POST['campaign_id'] ?? 0) ?: null;
    $talle    = trim($_POST['talle'] ?? '');
    $cantidad = max(1, min(10, (int)($_POST['cantidad'] ?? 1)));
    $notes    = trim($_POST['notes'] ?? '');

    /* Verificar que no tenga una solicitud pendiente para el mismo ítem */
    $exists = dbFetch(
        'SELECT id FROM solicitudes WHERE user_id=? AND campaign_id<=>? AND status="pending"',
        [$user['id'], $campId]
    );

    if ($exists) {
        $error = 'Ya tenés una solicitud pendiente para este ítem. Esperá que sea revisada.';
    } else {
        dbInsert('solicitudes', [
            'user_id'     => $user['id'],
            'campaign_id' => $campId,
            'dni'         => $user['dni'],
            'talle'       => $talle ?: null,
            'cantidad'    => $cantidad,
            'notes'       => $notes ?: null,
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $success = '¡Solicitud enviada correctamente! Te notificaremos cuando sea revisada.';
    }
}

/* Historial de solicitudes del usuario */
$misSolicitudes = dbFetchAll(
    'SELECT s.*, c.name AS camp_name, c.emoji AS camp_emoji,
            p.name AS parent_name
     FROM solicitudes s
     LEFT JOIN campaigns c ON c.id = s.campaign_id
     LEFT JOIN campaigns p ON p.id = c.parent_id
     WHERE s.user_id=? ORDER BY s.created_at DESC',
    [$user['id']]
);

$statusLabel = ['pending'=>'Pendiente','approved'=>'Aprobada','rejected'=>'Rechazada'];
$statusClass = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger'];
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=5.0">
<meta name="theme-color" content="#07050F">
<meta name="apple-mobile-web-app-capable" content="yes">
<title>Solicitar — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Raleway:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
<style>
/* ─── Colores gold para solicitudes ─── */
:root {
  --gold:#C8860A; --gold-mid:#D97706; --gold-bright:#F59E0B;
  --gold-light:#FCD34D; --gold-glow:rgba(245,158,11,.25);
}

/* ─── Hero interno ─── */
.sol-hero {
  background: linear-gradient(135deg, #0F0B1E 0%, #1a1000 50%, #0F0B1E 100%);
  border-bottom: 1px solid rgba(245,158,11,.15);
  padding: 2rem 1.25rem 1.75rem;
  position: relative; overflow: hidden;
}
.sol-hero::before {
  content:''; position:absolute; top:-80px; right:-80px;
  width:350px; height:350px; border-radius:50%;
  background: radial-gradient(circle, rgba(217,119,6,.18), transparent 70%);
  filter: blur(50px); pointer-events:none;
}
.sol-hero-inner {
  max-width: 780px; margin: 0 auto;
  display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap;
}
.sol-hero-icon {
  width: 56px; height: 56px; border-radius: 14px; flex-shrink: 0;
  background: linear-gradient(135deg, rgba(217,119,6,.3), rgba(245,158,11,.15));
  border: 1px solid rgba(245,158,11,.3);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.75rem;
}
.sol-hero-text h1 {
  font-family: 'Cinzel', serif; font-size: 1.35rem; color: #EDE9F6;
}
.sol-hero-text p { font-size:.88rem; color:#A0916E; margin-top:.2rem; }
.sol-badge-gold {
  display: inline-flex; align-items: center; gap: .35rem;
  background: rgba(245,158,11,.12); border: 1px solid rgba(245,158,11,.3);
  border-radius: 50px; padding: .25rem .85rem; font-size: .7rem;
  font-weight: 700; letter-spacing: .07em; color: #FCD34D;
  text-transform: uppercase; margin-bottom: .5rem;
}

/* ─── Tarjetas de campaña para solicitud ─── */
.sol-camp-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: .7rem;
}
.sol-camp-opt input { display:none; }
.sol-camp-card {
  background: #1A1430; border: 1px solid rgba(91,45,142,.25);
  border-radius: 12px; padding: 1rem .85rem; cursor: pointer;
  transition: .2s; text-align: center;
}
.sol-camp-card:hover { border-color: rgba(245,158,11,.4); }
.sol-camp-opt.selected .sol-camp-card,
.sol-camp-opt input:checked + .sol-camp-card {
  border-color: var(--gold-bright); background: rgba(245,158,11,.08);
  box-shadow: 0 0 0 2px rgba(245,158,11,.15);
}
.sol-camp-img { width:100%; height:70px; object-fit:cover; border-radius:8px; margin-bottom:.5rem; display:block; }
.sol-camp-emoji { font-size:1.8rem; margin-bottom:.4rem; line-height:1; display:block; }
.sol-camp-name { font-size:.8rem; font-weight:700; color:#EDE9F6; line-height:1.3; }
.sol-camp-parent { font-size:.7rem; color:#7B6A9B; margin-top:.15rem; }

/* ─── Talles ─── */
.talle-grid { display:flex; flex-wrap:wrap; gap:.5rem; }
.talle-opt input { display:none; }
.talle-btn {
  padding: .45rem .9rem; border-radius: 8px; cursor: pointer;
  background: #1A1430; border: 1px solid rgba(91,45,142,.25);
  color: #C4B5FD; font-size: .88rem; font-weight: 700;
  transition: .2s; font-family: 'Raleway', sans-serif;
}
.talle-btn:hover { border-color: rgba(245,158,11,.4); }
.talle-opt.selected .talle-btn,
.talle-opt input:checked + .talle-btn {
  background: rgba(245,158,11,.12); border-color: var(--gold-bright);
  color: var(--gold-light); box-shadow: 0 0 0 2px rgba(245,158,11,.15);
}

/* ─── Btn solicitar ─── */
.btn-solicitar {
  background: linear-gradient(135deg, #92400E, #B45309, #D97706);
  color: #fff; padding: .9rem 2rem; font-size: 1rem; border-radius: 50px;
  box-shadow: 0 6px 28px rgba(217,119,6,.4), 0 0 0 1px rgba(253,211,77,.15);
  font-family: 'Raleway', sans-serif; font-weight: 800;
  cursor: pointer; border: none; transition: .25s;
  display: flex; align-items: center; justify-content: center; gap: .5rem;
  width: 100%;
}
.btn-solicitar:hover:not(:disabled) {
  transform: translateY(-2px); box-shadow: 0 10px 38px rgba(217,119,6,.55);
}
.btn-solicitar:disabled { opacity: .5; cursor: not-allowed; }

/* ─── Card solicitud historial ─── */
.sol-card {
  display: flex; align-items: center; gap: 1rem;
  padding: .85rem 1rem; border-radius: 12px;
  background: rgba(255,255,255,.03); margin-bottom: .55rem;
  border: 1px solid transparent; transition: .2s;
}
.sol-card:hover { border-color: rgba(245,158,11,.15); }
.sol-card-icon {
  width: 40px; height: 40px; border-radius: 9px; flex-shrink: 0;
  background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.2);
  display: flex; align-items: center; justify-content: center; font-size: 1.15rem;
}
.sol-card-info { flex: 1; min-width: 0; }
.sol-card-name { font-size: .88rem; font-weight: 700; }
.sol-card-meta { font-size: .75rem; color: #7B6A9B; margin-top: .1rem; }
</style>
</head>
<body class="dark">

<nav class="navbar navbar-solid">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand">
      <img src="assets/logo.png" alt="FLB" class="nav-logo">
      <span class="brand-text"><span class="brand-main">COOPERATIVA</span><span class="brand-sub">FLB</span></span>
    </a>
    <div class="nav-links">
      <a href="index.php" class="nav-link">Inicio</a>
      <a href="donate.php" class="nav-link">Donar</a>
      <a href="dashboard.php" class="nav-link">Mi Panel</a>
      <a href="logout.php" class="nav-link">Salir</a>
    </div>
  </div>
</nav>

<div class="page-main">

  <!-- Hero interno -->
  <div class="sol-hero">
    <div class="sol-hero-inner">
      <div class="sol-hero-icon">📋</div>
      <div class="sol-hero-text">
        <div class="sol-badge-gold">✦ Solicitud de uniforme</div>
        <h1>Realizá tu Solicitud</h1>
        <p>Completá el formulario para solicitar tu uniforme. Un administrador revisará tu pedido.</p>
      </div>
    </div>
  </div>

  <div class="container container-sm" style="padding-top:1.75rem; padding-bottom:3rem;">

    <?php if ($error): ?>
      <div class="alert alert-error animate-up"><span>⚠</span><?= h($error) ?></div>
    <?php endif ?>
    <?php if ($success): ?>
      <div class="alert alert-success animate-up"><span>✅</span><?= h($success) ?></div>
    <?php endif ?>

    <!-- FORMULARIO -->
    <div class="section-card animate-up">
      <div class="section-card-header">
        <h2>📋 Nueva Solicitud</h2>
        <span style="font-size:.8rem;color:var(--text-muted)">DNI: <?= h($user['dni']) ?></span>
      </div>

      <form method="post" id="solForm">
        <?= csrfField() ?>

        <!-- Seleccionar ítem -->
        <div class="form-group">
          <label class="form-label" style="margin-bottom:.6rem">¿Qué necesitás? *</label>
          <?php if (empty($campaigns)): ?>
            <p style="color:var(--text-muted);font-size:.88rem">No hay campañas activas disponibles.</p>
          <?php else: ?>
          <div class="sol-camp-grid">
            <?php foreach ($campaigns as $c): ?>
            <label class="sol-camp-opt" onclick="this.classList.toggle('selected'); document.querySelectorAll('.sol-camp-opt').forEach(el=>{ if(el!==this)el.classList.remove('selected'); })">
              <input type="radio" name="campaign_id" value="<?= $c['id'] ?>" required>
              <div class="sol-camp-card">
                <?php if ($c['image']): ?>
                  <img src="<?= h($c['image']) ?>" alt="" class="sol-camp-img">
                <?php else: ?>
                  <span class="sol-camp-emoji"><?= h($c['emoji'] ?? '🎯') ?></span>
                <?php endif ?>
                <div class="sol-camp-name"><?= h($c['name']) ?></div>
                <?php if ($c['parent_name']): ?>
                  <div class="sol-camp-parent"><?= h($c['parent_name']) ?></div>
                <?php endif ?>
              </div>
            </label>
            <?php endforeach ?>
          </div>
          <?php endif ?>
        </div>

        <!-- Talle -->
        <div class="form-group" style="margin-top:1.25rem">
          <label class="form-label" style="margin-bottom:.6rem">Talle</label>
          <div class="talle-grid">
            <?php foreach (['XS','S','M','L','XL','XXL','XXXL','Único'] as $t): ?>
            <label class="talle-opt" onclick="document.querySelectorAll('.talle-opt').forEach(el=>el.classList.remove('selected')); this.classList.add('selected')">
              <input type="radio" name="talle" value="<?= $t ?>">
              <span class="talle-btn"><?= $t ?></span>
            </label>
            <?php endforeach ?>
          </div>
        </div>

        <!-- Cantidad -->
        <div class="form-group" style="margin-top:1.1rem">
          <label class="form-label">Cantidad</label>
          <div style="display:flex;align-items:center;gap:.75rem;margin-top:.35rem">
            <button type="button" onclick="changeQty(-1)" style="width:36px;height:36px;border-radius:8px;background:#1A1430;border:1px solid rgba(91,45,142,.25);color:#EDE9F6;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">−</button>
            <input type="number" name="cantidad" id="cantInput" class="form-input" value="1" min="1" max="10" style="width:70px;text-align:center;">
            <button type="button" onclick="changeQty(1)"  style="width:36px;height:36px;border-radius:8px;background:#1A1430;border:1px solid rgba(91,45,142,.25);color:#EDE9F6;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">+</button>
          </div>
        </div>

        <!-- Observaciones -->
        <div class="form-group" style="margin-top:1.1rem">
          <label class="form-label">Observaciones (opcional)</label>
          <textarea name="notes" class="form-input" rows="2" placeholder="Ej: pantalón azul talle especial, etc."></textarea>
        </div>

        <!-- Info DNI -->
        <div class="form-notice" style="margin-top:1rem;background:rgba(245,158,11,.07);border-color:rgba(245,158,11,.25);color:#FCD34D;">
          <span>ℹ️</span>
          Tu DNI <strong><?= h($user['dni']) ?></strong> se asociará automáticamente a esta solicitud. Un administrador la revisará y te notificará el resultado desde tu panel.
        </div>

        <button type="submit" class="btn-solicitar" style="margin-top:1rem">
          <span>📋</span> Enviar Solicitud
        </button>
      </form>
    </div>

    <!-- HISTORIAL -->
    <?php if (!empty($misSolicitudes)): ?>
    <div class="section-card animate-up" style="animation-delay:.1s; margin-top:1.25rem;">
      <div class="section-card-header">
        <h2>Mis Solicitudes</h2>
        <span class="badge badge-info"><?= count($misSolicitudes) ?> registros</span>
      </div>
      <?php foreach ($misSolicitudes as $s): ?>
      <div class="sol-card">
        <div class="sol-card-icon"><?= h($s['camp_emoji'] ?? '📋') ?></div>
        <div class="sol-card-info">
          <div class="sol-card-name">
            <?php if ($s['parent_name']): ?>
              <span style="color:var(--text-muted);font-size:.8rem"><?= h($s['parent_name']) ?> › </span>
            <?php endif ?>
            <?= h($s['camp_name'] ?? 'Fondo General') ?>
          </div>
          <div class="sol-card-meta">
            <?= $s['talle'] ? 'Talle ' . h($s['talle']) . ' · ' : '' ?>
            Cant. <?= $s['cantidad'] ?> ·
            DNI <?= h($s['dni']) ?> ·
            <?= date('d/m/Y', strtotime($s['created_at'])) ?>
            <?php if ($s['rejection_reason']): ?>
              <br><span style="color:#FCA5A5">Motivo: <?= h($s['rejection_reason']) ?></span>
            <?php endif ?>
          </div>
        </div>
        <span class="badge <?= $statusClass[$s['status']] ?>"><?= $statusLabel[$s['status']] ?></span>
      </div>
      <?php endforeach ?>
    </div>
    <?php endif ?>

    <div style="text-align:center;margin-top:1.25rem">
      <a href="dashboard.php" style="color:var(--text-muted);font-size:.875rem">← Volver a mi panel</a>
    </div>

  </div>
</div>

<script src="assets/app.js"></script>
<script>
function changeQty(d) {
  const inp = document.getElementById('cantInput');
  let v = parseInt(inp.value) + d;
  inp.value = Math.min(10, Math.max(1, v));
}
</script>
</body></html>
