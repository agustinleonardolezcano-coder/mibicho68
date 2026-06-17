<?php
require_once __DIR__ . '/functions.php';
$user = getCurrentUser();

try {
    $planes = dbFetchAll(
        'SELECT * FROM comisiones WHERE activo = 1 ORDER BY sort_order ASC'
    );
} catch (Exception $e) {
    $planes = [];
}

$periodoLabel = [
    'mensual'      => '/ mes',
    'trimestral'   => '/ trimestre',
    'semestral'    => '/ semestre',
    'anual'        => '/ año',
];
?><!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=5.0">
<meta name="theme-color" content="#050D1F">
<title>Cuotas y Tarifas — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Raleway:wght@300;400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
<style>
/* ══ COMISIONES / TARIFAS PAGE ══════════════════════════════ */
body { background: #050D1F; color: #EFF6FF; }

/* Hero */
.tar-hero {
  padding: 7rem 1.25rem 3.5rem;
  text-align: center;
  background: radial-gradient(ellipse 70% 50% at 50% 0%, rgba(124,58,237,.25), transparent 70%);
}
.tar-hero-tag {
  display: inline-block;
  font-size: .72rem; font-weight: 700;
  letter-spacing: .12em; text-transform: uppercase;
  color: #A78BFA;
  background: rgba(124,58,237,.1);
  border: 1px solid rgba(124,58,237,.3);
  border-radius: 30px; padding: .3rem 1rem; margin-bottom: 1.25rem;
}
.tar-hero h1 {
  font-family: 'Cinzel', serif;
  font-size: clamp(1.8rem, 4vw, 3rem);
  color: #EFF6FF; margin-bottom: .85rem; line-height: 1.2;
}
.tar-hero h1 span { color: #A78BFA; }
.tar-hero p {
  color: #BFDBFE; max-width: 560px; margin: 0 auto;
  font-size: 1rem; line-height: 1.7;
}

/* Period toggle */
.tar-period-wrap {
  display: flex; justify-content: center; gap: .5rem;
  padding: 1.5rem 1.25rem 0; flex-wrap: wrap;
}
.tar-period-btn {
  padding: .45rem 1.1rem;
  border-radius: 999px;
  border: 1.5px solid rgba(255,255,255,.12);
  background: transparent;
  color: #7B6A9B;
  font-family: 'Raleway', sans-serif; font-size: .82rem; font-weight: 700;
  cursor: pointer; transition: all .18s;
  text-transform: capitalize;
}
.tar-period-btn:hover { border-color: rgba(167,139,250,.4); color: #A78BFA; }
.tar-period-btn.active {
  background: linear-gradient(135deg, rgba(124,58,237,.3), rgba(168,85,247,.2));
  border-color: rgba(124,58,237,.6); color: #EFF6FF;
  box-shadow: 0 0 16px rgba(124,58,237,.2);
}

/* Grid */
.tar-grid-wrap {
  max-width: 1060px; margin: 0 auto;
  padding: 3rem 1.25rem 5.5rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
  align-items: start;
}

/* Card */
.tar-card {
  background: rgba(8,15,39,.85);
  border: 1px solid rgba(124,58,237,.18);
  border-radius: 22px;
  padding: 2rem 1.75rem;
  position: relative;
  transition: transform .22s, box-shadow .22s, border-color .22s;
  overflow: hidden;
}
.tar-card::before {
  content: ''; position: absolute; inset: 0;
  background: radial-gradient(ellipse at top, rgba(124,58,237,.07), transparent 60%);
  pointer-events: none;
}
.tar-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 16px 48px rgba(124,58,237,.22);
  border-color: rgba(124,58,237,.42);
}

/* Destacado */
.tar-card.destacado {
  border-color: rgba(124,58,237,.55);
  background: rgba(10,7,30,.92);
  box-shadow: 0 0 32px rgba(124,58,237,.18);
}
.tar-card.destacado::before {
  background: radial-gradient(ellipse at top, rgba(124,58,237,.15), transparent 65%);
}
.tar-badge-dest {
  display: inline-flex; align-items: center; gap: .3rem;
  font-size: .68rem; font-weight: 800;
  letter-spacing: .08em; text-transform: uppercase;
  color: #EFF6FF;
  background: linear-gradient(90deg, #7C3AED, #A855F7);
  border-radius: 30px; padding: .25rem .85rem;
  margin-bottom: 1.25rem;
  box-shadow: 0 2px 12px rgba(124,58,237,.4);
}

.tar-name {
  font-family: 'Cinzel', serif;
  font-size: 1.2rem; font-weight: 700;
  color: #EFF6FF; margin-bottom: .4rem;
}
.tar-desc {
  font-size: .83rem; color: #7B8BB5;
  line-height: 1.55; margin-bottom: 1.5rem;
}

/* Precio */
.tar-price-row {
  display: flex; align-items: flex-end; gap: .35rem;
  margin-bottom: 1.5rem;
}
.tar-currency {
  font-family: 'Cinzel', serif;
  font-size: .95rem; color: #A78BFA;
  line-height: 1; padding-bottom: .35rem;
}
.tar-amount {
  font-family: 'Cinzel', serif;
  font-size: 2.6rem; font-weight: 900;
  color: #EFF6FF; line-height: 1;
}
.tar-periodo {
  font-size: .8rem; color: #4B6A9B;
  padding-bottom: .3rem;
}

/* Divider */
.tar-divider {
  height: 1px;
  background: linear-gradient(90deg, rgba(124,58,237,.3), transparent);
  margin-bottom: 1.35rem;
}

/* Features */
.tar-features { list-style: none; padding: 0; margin: 0 0 1.75rem; }
.tar-features li {
  display: flex; align-items: flex-start; gap: .6rem;
  font-size: .85rem; color: #BFDBFE;
  padding: .35rem 0;
  border-bottom: 1px solid rgba(255,255,255,.04);
}
.tar-features li:last-child { border-bottom: none; }
.tar-feat-icon {
  flex-shrink: 0; width: 18px; height: 18px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .65rem; margin-top: .05rem;
  background: rgba(124,58,237,.2); color: #A78BFA;
}

/* CTA */
.tar-cta {
  display: block; width: 100%;
  padding: .7rem 1rem; border-radius: 10px;
  font-family: 'Raleway', sans-serif;
  font-size: .88rem; font-weight: 700;
  text-align: center; cursor: pointer;
  transition: all .18s; border: none; text-decoration: none;
  background: rgba(124,58,237,.15);
  border: 1.5px solid rgba(124,58,237,.35);
  color: #A78BFA;
}
.tar-cta:hover {
  background: rgba(124,58,237,.28);
  border-color: rgba(124,58,237,.65);
  color: #EFF6FF;
}
.tar-card.destacado .tar-cta {
  background: linear-gradient(135deg, #7C3AED, #9333EA);
  border-color: transparent; color: #fff;
  box-shadow: 0 4px 18px rgba(124,58,237,.4);
}
.tar-card.destacado .tar-cta:hover {
  background: linear-gradient(135deg, #6D28D9, #7C3AED);
  box-shadow: 0 6px 24px rgba(124,58,237,.55);
}

/* Estado vacío */
.tar-empty {
  text-align: center; padding: 5rem 1.25rem;
  color: #4B6A9B;
}
.tar-empty-icon { font-size: 3rem; margin-bottom: 1rem; }
.tar-empty p { font-size: .95rem; }

/* Info footer */
.tar-info {
  max-width: 680px; margin: 0 auto;
  padding: 0 1.25rem 5rem;
  text-align: center;
  font-size: .84rem; color: #4B6A9B;
  line-height: 1.65;
}
.tar-info a { color: #A78BFA; }

@media (max-width: 600px) {
  .tar-grid-wrap { grid-template-columns: 1fr; gap: 1.1rem; padding-bottom: 4rem; }
  .tar-card { padding: 1.5rem 1.2rem; }
  .tar-amount { font-size: 2.1rem; }
}
</style>
</head>
<body class="dark">

<!-- NAV -->
<nav class="navbar" id="navbar">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand">
      <img src="assets/logo.png" alt="FLB" class="nav-logo">
      <span class="brand-text">
        <span class="brand-main">COOPERATIVA</span>
        <span class="brand-sub">Fray Luis Beltrán</span>
      </span>
    </a>
    <div class="nav-links">
      <a href="index.php#campañas"      class="nav-link">Campañas</a>
      <a href="index.php#como-funciona" class="nav-link">¿Cómo funciona?</a>
      <a href="solicitar-servicio.php"  class="nav-link">🔧 Servicios</a>
      <a href="nosotros.php"            class="nav-link">Nosotros</a>
      <a href="comisiones.php"          class="nav-link active" style="color:#A78BFA">Cuotas</a>
      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="admin/index.php" class="nav-link">Admin</a>
        <?php endif ?>
        <a href="dashboard.php" class="btn btn-outline-sm">Mi Panel</a>
        <a href="logout.php" class="nav-link">Salir</a>
      <?php else: ?>
        <a href="login.php" class="nav-link">Ingresar</a>
        <a href="register.php" class="btn btn-primary-sm">Registrarse</a>
      <?php endif ?>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Menú">
      <span></span><span></span><span></span>
    </button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <a href="index.php">Inicio</a>
    <a href="index.php#campañas">Campañas</a>
    <a href="nosotros.php">Nosotros</a>
    <a href="comisiones.php" style="color:#A78BFA">Cuotas</a>
    <?php if ($user): ?>
      <a href="dashboard.php">Mi Panel</a>
      <a href="solicitar.php">📋 Solicitar Uniforme</a>
      <a href="solicitar-servicio.php">🔧 Solicitar Servicio</a>
      <a href="logout.php">Salir</a>
    <?php else: ?>
      <a href="login.php">Ingresar</a>
      <a href="register.php">Registrarse</a>
    <?php endif ?>
  </div>
</nav>

<!-- HERO -->
<div class="tar-hero">
  <div class="tar-hero-tag">✦ Cooperativa Fray Luis Beltrán ✦</div>
  <h1>Cuotas y <span>Tarifas</span></h1>
  <p>Elegí el plan que mejor se adapta a vos. Todas las cuotas dan acceso a los beneficios y servicios de la cooperativa.</p>
</div>

<?php if (empty($planes)): ?>
<!-- VACÍO -->
<div class="tar-empty">
  <div class="tar-empty-icon">💳</div>
  <p>Todavía no hay tarifas publicadas.<br>Volvé pronto.</p>
</div>

<?php else: ?>
<!-- PLANES -->
<div class="tar-grid-wrap">
  <?php foreach ($planes as $p): ?>
  <?php
    $isDestacado = (bool)$p['destacado'];
    $features    = array_filter(array_map('trim', explode("\n", $p['caracteristicas'] ?? '')));
    $periodoSuf  = $periodoLabel[$p['periodo']] ?? '/ mes';
    $precioFmt   = number_format($p['precio'], 0, ',', '.');
  ?>
  <div class="tar-card <?= $isDestacado ? 'destacado' : '' ?> animate-up">

    <?php if ($isDestacado): ?>
      <div class="tar-badge-dest">⭐ Recomendado</div>
    <?php endif ?>

    <div class="tar-name"><?= htmlspecialchars($p['nombre']) ?></div>
    <?php if (!empty($p['descripcion'])): ?>
      <div class="tar-desc"><?= htmlspecialchars($p['descripcion']) ?></div>
    <?php endif ?>

    <div class="tar-price-row">
      <span class="tar-currency">$</span>
      <span class="tar-amount"><?= $precioFmt ?></span>
      <span class="tar-periodo"><?= $periodoSuf ?></span>
    </div>

    <div class="tar-divider"></div>

    <?php if (!empty($features)): ?>
    <ul class="tar-features">
      <?php foreach ($features as $feat): ?>
      <li>
        <span class="tar-feat-icon">✓</span>
        <?= htmlspecialchars($feat) ?>
      </li>
      <?php endforeach ?>
    </ul>
    <?php endif ?>

    <?php if ($user): ?>
      <a href="dashboard.php" class="tar-cta">Gestionar mi cuota</a>
    <?php else: ?>
      <a href="register.php" class="tar-cta">Asociarse ahora</a>
    <?php endif ?>

  </div>
  <?php endforeach ?>
</div>

<div class="tar-info">
  ¿Tenés preguntas sobre las cuotas? Contactanos a través del
  <a href="index.php#contacto">formulario de contacto</a> o acercate a la cooperativa.
  Los precios se expresan en pesos argentinos e incluyen todos los impuestos aplicables.
</div>
<?php endif ?>

<!-- FOOTER -->
<footer class="footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <img src="assets/logo.png" alt="FLB" style="width:50px;opacity:.8">
      <div>
        <div class="footer-name">Cooperativa FLB</div>
        <div class="footer-sub">Fray Luis Beltrán</div>
      </div>
    </div>
    <div class="footer-links">
      <a href="index.php">Inicio</a>
      <a href="index.php#campañas">Campañas</a>
      <a href="nosotros.php">Nosotros</a>
      <a href="login.php">Ingresar</a>
    </div>
    <div class="footer-copy">© <?= date('Y') ?> Cooperativa Fray Luis Beltrán. Todos los derechos reservados.</div>
  </div>
</footer>

<script src="assets/app.js"></script>
</body>
</html>
