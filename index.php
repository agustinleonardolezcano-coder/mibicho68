<?php
ob_start();
require_once __DIR__ . '/functions.php';
$user      = getCurrentUser();
$stats     = getStats();
$goal      = (float)getSetting('goal_total', '500000');
$pct       = $goal > 0 ? min(100, round(($stats['total'] / $goal) * 100, 1)) : 0;
$heroMsg   = getSetting('hero_message');
$subtitle  = getSetting('site_subtitle');

/* ─── Equipo / Nosotros ─── */
try {
    $team = dbFetchAll('SELECT * FROM team_members ORDER BY section, sort_order ASC');
} catch (Exception $e) {
    $team = [];
}
$sections = [
    'consejo_autoridades' => ['title' => 'Consejo de Administración', 'subtitle' => 'Autoridades',          'icon' => '🏛️'],
    'consejo_sindicos'    => ['title' => 'Consejo de Administración', 'subtitle' => 'Síndicos Titulares',   'icon' => '🏛️'],
    'junta_escrutadora'   => ['title' => 'Junta Escrutadora de Asamblea', 'subtitle' => '4 Titulares',      'icon' => '🗳️'],
    'junta_revisora'      => ['title' => 'Junta Revisora de Cuentas', 'subtitle' => '3 Titulares',          'icon' => '📊'],
    'docentes_asesores'   => ['title' => 'Docentes Asesores', 'subtitle' => '',                             'icon' => '🎓'],
    'vocalistas'          => ['title' => 'Vocalistas',         'subtitle' => '',                             'icon' => '🎤'],
];

$allCampaigns = dbFetchAll('SELECT * FROM campaigns WHERE status="active" ORDER BY parent_id IS NOT NULL, parent_id, created_at');
$parentCamps  = [];
$subCamps     = [];
foreach ($allCampaigns as $c) {
    if ($c['parent_id']) $subCamps[$c['parent_id']][] = $c;
    else $parentCamps[] = $c;
}
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="theme-color" content="#0a0520">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title><?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Syne:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Keep original style.css for admin/shared components -->
<link rel="stylesheet" href="assets/style.css">
<!-- Aurora premium redesign -->
<link rel="stylesheet" href="assets/style-aurora.css">

<style>
/* ══════════════════════════════════════════
   COOPERATIVA FLB — Rediseño Institucional
   Tipografía formal + animaciones + elegancia
   Colores originales conservados
══════════════════════════════════════════ */

/* ── TIPOGRAFÍA GLOBAL ─────────────────── */
body, p, li, span, a, div {
  font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif !important;
  letter-spacing: -0.01em;
}

/* Títulos de sección: serifa elegante */
h1, h2, h3,
.section-title,
.hero-title,
.path-title,
.cta-title {
  font-family: 'DM Serif Display', Georgia, serif !important;
  letter-spacing: -0.02em !important;
  font-weight: 400 !important;
}

/* Labels, eyebrows, chips: sans-serif monospaced-feel */
.section-eyebrow,
.hero-badge,
.path-chip,
.stat-label,
.nos-role,
.footer-sub {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  letter-spacing: 0.12em !important;
  text-transform: uppercase !important;
  font-size: 0.70rem !important;
  font-weight: 600 !important;
}

/* Navbar brand */
.brand-main {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-weight: 700 !important;
  letter-spacing: 0.08em !important;
  font-size: 0.78rem !important;
}
.nav-link {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-weight: 500 !important;
  font-size: 0.83rem !important;
  letter-spacing: 0.02em !important;
}

/* ── HERO: tamaño elegante y proporcionado ─ */
.hero-title {
  font-size: clamp(3.2rem, 6.5vw, 5.8rem) !important;
  line-height: 1.05 !important;
  white-space: nowrap !important;
}
.title-line { display: block; }

/* Hero badge: más refinado */
.hero-badge {
  font-size: 0.68rem !important;
  letter-spacing: 0.16em !important;
  padding: 0.45rem 1.2rem !important;
  border-radius: 2px !important;
}

/* Hero desc: más espaciado */
.hero-desc {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-size: 1.0rem !important;
  line-height: 1.75 !important;
  letter-spacing: 0.01em !important;
  max-width: 520px;
}

/* ── SECCIONES: más espacio, más respiro ── */
.section-title {
  font-size: clamp(2.2rem, 4vw, 3.4rem) !important;
  line-height: 1.1 !important;
  margin-bottom: 1rem !important;
}
.section-eyebrow {
  color: var(--cyan-soft, #67e8f9) !important;
  margin-bottom: 0.75rem !important;
  display: block;
}

/* ── BOTONES: bordes rectos + tracking ─── */
.btn,
.btn-hero,
.btn-hero-outline,
.btn-nav-primary,
.btn-nav-outline,
.path-cta,
.btn-campaign,
.btn-primary {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-weight: 600 !important;
  letter-spacing: 0.04em !important;
  border-radius: 4px !important;
  font-size: 0.82rem !important;
  text-transform: uppercase;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

/* ── PATH CARDS: títulos serif ─────────── */
.path-title {
  font-size: clamp(1.6rem, 2.5vw, 2.1rem) !important;
  line-height: 1.15 !important;
  margin-bottom: 0.75rem !important;
}

/* Path features: tipografía más limpia */
.path-features li {
  font-size: 0.83rem !important;
  letter-spacing: 0.01em !important;
  line-height: 1.6 !important;
  font-weight: 400 !important;
}

/* ── STATS: valores numéricos ─────────── */
.stat-value {
  font-family: 'DM Serif Display', serif !important;
  font-size: clamp(2rem, 4vw, 2.8rem) !important;
  letter-spacing: -0.03em !important;
  line-height: 1 !important;
}
.stat-label {
  font-size: 0.72rem !important;
  margin-top: 0.4rem !important;
}

/* ── CAMPAIGN CARDS ───────────────────── */
.campaign-name {
  font-family: 'DM Serif Display', serif !important;
  font-size: 1.35rem !important;
  font-weight: 400 !important;
  letter-spacing: -0.02em !important;
  line-height: 1.25 !important;
}
.campaign-desc {
  font-size: 0.83rem !important;
  line-height: 1.65 !important;
}

/* ── HOW STEPS: numeración tipográfica ── */
.step-num {
  font-family: 'DM Serif Display', serif !important;
  font-size: 1.2rem !important;
  font-weight: 400 !important;
  min-width: 2.2rem !important;
  height: 2.2rem !important;
  border-radius: 3px !important;
}
.step-text strong {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-weight: 700 !important;
  font-size: 0.87rem !important;
  letter-spacing: 0.01em !important;
  display: block;
  margin-bottom: 0.2rem;
}
.step-text {
  font-size: 0.82rem !important;
  line-height: 1.6 !important;
}

/* ── ABOUT / NOSOTROS ─────────────────── */
.quote-text {
  font-family: 'DM Serif Display', serif !important;
  font-size: 1.15rem !important;
  font-style: italic !important;
  line-height: 1.7 !important;
  letter-spacing: 0.0em !important;
}
.quote-mark {
  font-family: 'DM Serif Display', serif !important;
  font-size: 5rem !important;
  line-height: 0.6 !important;
  opacity: 0.25 !important;
}
.quote-author {
  font-size: 0.75rem !important;
  letter-spacing: 0.12em !important;
  text-transform: uppercase !important;
}

/* Nombre miembros de equipo */
.nos-name {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-weight: 700 !important;
  font-size: 0.85rem !important;
  letter-spacing: 0.01em !important;
}

/* ── CTA SECTION ──────────────────────── */
.cta-title {
  font-size: clamp(2.4rem, 5vw, 4rem) !important;
  line-height: 1.1 !important;
}
.cta-desc {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-size: 0.95rem !important;
  line-height: 1.75 !important;
  letter-spacing: 0.01em !important;
}

/* ── FOOTER ───────────────────────────── */
.footer-name {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-weight: 700 !important;
  letter-spacing: 0.06em !important;
  text-transform: uppercase !important;
  font-size: 0.78rem !important;
}
.footer-links a {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-size: 0.80rem !important;
  letter-spacing: 0.04em !important;
  font-weight: 500 !important;
}
.footer-copy {
  font-size: 0.72rem !important;
  letter-spacing: 0.03em !important;
}

/* ── ANIMACIONES DE ENTRADA ───────────── */

/* Fade-up base */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(28px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Fade-in */
@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* Slide desde izquierda */
@keyframes slideRight {
  from { opacity: 0; transform: translateX(-24px); }
  to   { opacity: 1; transform: translateX(0); }
}

/* Reveal de línea (clipPath) */
@keyframes revealLine {
  from { clip-path: inset(0 100% 0 0); opacity: 0; }
  to   { clip-path: inset(0 0% 0 0);   opacity: 1; }
}

/* Scale in para tarjetas */
@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.96) translateY(16px); }
  to   { opacity: 1; transform: scale(1)    translateY(0); }
}

/* ── HERO: animación por línea ─────────── */
.hero-badge {
  animation: fadeIn 0.6s ease both;
  animation-delay: 0.1s;
}
.title-line:nth-child(1) {
  animation: fadeUp 0.8s cubic-bezier(0.22,1,0.36,1) both;
  animation-delay: 0.3s;
}
.title-line:nth-child(2) {
  animation: fadeUp 0.8s cubic-bezier(0.22,1,0.36,1) both;
  animation-delay: 0.5s;
}
.title-line:nth-child(3) {
  animation: fadeUp 0.8s cubic-bezier(0.22,1,0.36,1) both;
  animation-delay: 0.7s;
}
.hero-desc {
  animation: fadeUp 0.7s ease both;
  animation-delay: 0.9s;
}
.hero-actions {
  animation: fadeUp 0.7s ease both;
  animation-delay: 1.1s;
}
.hero-progress {
  animation: fadeUp 0.7s ease both;
  animation-delay: 1.3s;
}

/* ── SCROLL ANIMATIONS (CSS-only fallback) */
/* Las clases .animate-up ya existen; las mejoramos */
.animate-up {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.7s cubic-bezier(0.22,1,0.36,1),
              transform 0.7s cubic-bezier(0.22,1,0.36,1) !important;
}
.animate-up.in-view {
  opacity: 1 !important;
  transform: translateY(0) !important;
}

/* Section eyebrow reveal */
.section-eyebrow {
  animation: slideRight 0.5s ease both;
}

/* ── HOVER EN CARDS: lift effect ────────── */
.glass-card,
.path-card,
.campaign-card,
.nos-card,
.how-track {
  transition: transform 0.3s cubic-bezier(0.22,1,0.36,1),
              box-shadow 0.3s ease,
              border-color 0.3s ease !important;
}
.glass-card:hover,
.path-card:hover,
.campaign-card:hover,
.nos-card:hover {
  transform: translateY(-4px) !important;
  box-shadow: 0 20px 48px rgba(0,0,0,0.35) !important;
}

/* ── PROGRESS BAR: transición suave ─────── */
.progress-bar-fill,
.cp-fill {
  transition: width 1.4s cubic-bezier(0.22,1,0.36,1) !important;
}

/* ── NÚMEROS DE SECCIÓN (estilo Newform) ── */
.how-track-header span {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-weight: 700 !important;
  font-size: 0.78rem !important;
  letter-spacing: 0.10em !important;
  text-transform: uppercase !important;
}

/* ── BOTÓN CAMPAIGN: más refinado ───────── */
.btn-campaign {
  padding: 0.55rem 1.1rem !important;
  font-size: 0.75rem !important;
}

/* ── MOBILE MENU: tipografía limpia ─────── */
.mobile-menu a {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-weight: 600 !important;
  letter-spacing: 0.06em !important;
  font-size: 0.9rem !important;
  text-transform: uppercase !important;
}

/* ── LINKS NAV: underline animado ───────── */
.nav-link {
  position: relative;
  padding-bottom: 2px;
}
.nav-link::after {
  content: '';
  position: absolute;
  bottom: -2px; left: 0;
  width: 0; height: 1px;
  background: currentColor;
  transition: width 0.25s ease;
}
.nav-link:hover::after { width: 100%; }

</style>
</head>
<body>

<!-- ═══════════════ AURORA WebGL CANVAS ═══════════════ -->
<canvas id="aurora-canvas"></canvas>

<!-- ═══════════════ NAVBAR ═══════════════ -->
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
      <a href="#campañas" class="nav-link">Campañas</a>
      <a href="#como-funciona" class="nav-link">¿Cómo funciona?</a>
      <a href="solicitar-servicio.php" class="nav-link">🔧 Servicios</a>
      <a href="nosotros.php" class="nav-link">Nosotros</a>
      <a href="comisiones.php" class="nav-link">Comisiones</a>
      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="admin/index.php" class="nav-link">Admin</a>
        <?php endif ?>
        <a href="dashboard.php" class="btn-nav-outline">Mi Panel</a>
        <a href="logout.php" class="nav-link">Salir</a>
      <?php else: ?>
        <a href="login.php" class="nav-link">Ingresar</a>
        <a href="register.php" class="btn-nav-primary">Registrarse</a>
      <?php endif ?>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Menú">
      <span></span><span></span><span></span>
    </button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <a href="#campañas">Campañas</a>
    <a href="#como-funciona">¿Cómo funciona?</a>
    <a href="nosotros.php">Nosotros</a>
    <a href="comisiones.php">Comisiones</a>
    <a href="solicitar-servicio.php">🔧 Servicios</a>
    <?php if ($user): ?>
      <a href="dashboard.php">Mi Panel</a>
      <a href="solicitar.php">📋 Solicitar Uniforme</a>
      <a href="logout.php">Salir</a>
    <?php else: ?>
      <a href="login.php">Ingresar</a>
      <a href="register.php">Registrarse</a>
    <?php endif ?>
  </div>
</nav>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="hero" id="hero">
  <div class="hero-content">
    <div class="hero-badge">✦ Cooperativa Escolar Fray Luis Beltrán ✦</div>
    <h1 class="hero-title">
      <span class="title-line">Construyamos</span>
      <span class="title-line gradient-text">el Futuro</span>
      <span class="title-line">Juntos</span>
    </h1>
    <p class="hero-desc"><?= h($heroMsg) ?></p>
    <div class="hero-actions">
      <?php if ($user): ?>
        <a href="donate.php"    class="btn btn-hero">💜 Realizar Donación</a>
        <a href="solicitar.php" class="btn btn-hero-outline">📋 Solicitar Uniforme</a>
      <?php else: ?>
        <a href="register.php"      class="btn btn-hero">✦ Quiero Participar</a>
        <a href="#como-funciona"    class="btn btn-hero-outline">¿Cómo funciona?</a>
      <?php endif ?>
    </div>
    <div class="hero-progress">
      <div class="progress-labels">
        <span class="progress-raised">Recaudado: <strong class="counter" data-target="<?= $stats['total'] ?>" data-format="money">$0</strong></span>
        <span class="progress-pct"><?= $pct ?>% de la meta</span>
      </div>
      <div class="progress-bar-wrap">
        <div class="progress-bar-fill" style="--pct:<?= $pct ?>%"></div>
      </div>
      <div class="progress-goal">Meta: <?= money($goal) ?></div>
    </div>
  </div>
</section>

<!-- ═══════════════ BENTO STATS ═══════════════ -->
<div class="bento-stats">
  <div class="bento-grid">

    <!-- STAT 1: Total recaudado — wide card -->
    <div class="glass-card glass-card-accent bento-span2 stat-card animate-up">
      <div class="stat-icon-wrap si-blue">💰</div>
      <div class="stat-value text-cyan counter" data-target="<?= $stats['total'] ?>" data-format="money">$0</div>
      <div class="stat-label">Total Recaudado</div>
      <span class="stat-trend">↑ Activo</span>
    </div>

    <!-- STAT 2 -->
    <div class="glass-card glass-card-purple stat-card animate-up" style="animation-delay:.08s">
      <div class="stat-icon-wrap si-purple">🙌</div>
      <div class="stat-value text-purple counter" data-target="<?= $stats['donors'] ?>">0</div>
      <div class="stat-label">Donantes Activos</div>
    </div>

    <!-- STAT 3 -->
    <div class="glass-card glass-card-purple stat-card animate-up" style="animation-delay:.16s">
      <div class="stat-icon-wrap si-cyan">📅</div>
      <div class="stat-value text-blue counter" data-target="<?= $stats['month'] ?>" data-format="money">$0</div>
      <div class="stat-label">Donado este Mes</div>
    </div>

    <!-- STAT 4 -->
    <div class="glass-card glass-card-amber stat-card animate-up" style="animation-delay:.24s">
      <div class="stat-icon-wrap si-amber">🎯</div>
      <div class="stat-value text-amber counter" data-target="<?= $stats['camps'] ?>">0</div>
      <div class="stat-label">Campañas Activas</div>
    </div>

    <!-- STAT 5: CTA inline -->
    <div class="glass-card glass-card-accent bento-span2 animate-up" style="animation-delay:.32s; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
      <div>
        <div style="font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:800; margin-bottom:0.3rem; color:var(--text-primary);">¿Listo para contribuir?</div>
        <div style="font-size:0.85rem; color:var(--text-muted);">Cada peso transforma realidades en nuestra institución.</div>
      </div>
      <?php if ($user): ?>
        <a href="donate.php" class="btn btn-primary">💜 Donar Ahora</a>
      <?php else: ?>
        <a href="register.php" class="btn btn-primary">✦ Registrarse</a>
      <?php endif ?>
    </div>

  </div>
</div>

<!-- ═══════════════ DOS CAMINOS ═══════════════ -->
<section class="dual-section" id="como-funciona">
  <div class="section-header">
    <div class="section-eyebrow">¿Qué querés hacer?</div>
    <h2 class="section-title">Tres formas de participar</h2>
    <p class="section-desc">Apoyá económicamente, solicitá tu uniforme, o pedí un servicio técnico de nuestros talleres.</p>
  </div>

  <div class="path-grid">

    <!-- DONAR -->
    <div class="path-card path-donate animate-up">
      <div class="path-orb"></div>
      <span class="path-chip">Para todos</span>
      <div class="path-icon-wrap">💜</div>
      <h3 class="path-title">Realizá una<br>Donación</h3>
      <p class="path-desc">Apoyá económicamente las campañas. Cada peso va directamente a mejorar la educación.</p>
      <ul class="path-features">
        <li>Elegí el monto que quieras aportar</li>
        <li>Seleccioná la campaña de tu preferencia</li>
        <li>Pago seguro vía MercadoPago o tarjeta</li>
        <li>Recibí tu comprobante al instante</li>
      </ul>
      <?php if ($user): ?>
        <a href="donate.php" class="path-cta cta-donate">💜 Donar Ahora</a>
      <?php else: ?>
        <a href="register.php" class="path-cta cta-donate">Registrarse para donar</a>
      <?php endif ?>
    </div>

    <!-- SOLICITAR UNIFORME -->
    <div class="path-card path-request animate-up" style="animation-delay:.1s">
      <div class="path-orb"></div>
      <span class="path-chip">Para alumnos</span>
      <div class="path-icon-wrap">📋</div>
      <h3 class="path-title">Solicitá tu<br>Uniforme</h3>
      <p class="path-desc">Si sos alumno/a, podés solicitar tu ropa de trabajo, chomba o uniforme de educación física.</p>
      <ul class="path-features">
        <li>Usá tu DNI para identificarte</li>
        <li>Elegí el ítem y talle que necesitás</li>
        <li>Un administrador revisará tu pedido</li>
        <li>Seguí el estado desde tu panel</li>
      </ul>
      <?php if ($user): ?>
        <a href="solicitar.php" class="path-cta cta-request">📋 Hacer Solicitud</a>
      <?php else: ?>
        <a href="register.php" class="path-cta cta-request">Registrarse para solicitar</a>
      <?php endif ?>
    </div>

    <!-- SERVICIOS -->
    <div class="path-card path-service animate-up" style="animation-delay:.2s">
      <div class="path-orb"></div>
      <span class="path-chip">Talleres FLB</span>
      <div class="path-icon-wrap">🔧</div>
      <h3 class="path-title">Solicitá un<br>Servicio</h3>
      <p class="path-desc">Nuestros talleres ofrecen servicios técnicos reales: computación, electrodomésticos, mecánica y pintura.</p>
      <ul class="path-features">
        <li>💻 Taller 1 — Tecnología y Computación</li>
        <li>🔌 Taller 2 — Electrodomésticos y Eléctrico</li>
        <li>🚗 Taller 3 — Automotores y Pintura</li>
        <li>Aprobación rápida y seguimiento</li>
      </ul>
      <?php if ($user): ?>
        <a href="solicitar-servicio.php" class="path-cta cta-service">🔧 Ver Servicios</a>
      <?php else: ?>
        <a href="register.php" class="path-cta cta-service">Registrarse para solicitar</a>
      <?php endif ?>
    </div>

  </div>
</section>

<!-- ═══════════════ CÓMO FUNCIONA (pasos) ═══════════════ -->
<section class="how-section">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow">Paso a paso</div>
      <h2 class="section-title">¿Cómo funciona?</h2>
    </div>
    <div class="how-grid">

      <!-- Track donación -->
      <div class="how-track animate-up">
        <div class="how-track-header">
          <div class="how-track-dot dot-blue">💜</div>
          <span>Track de Donación</span>
        </div>
        <div class="how-steps">
          <div class="how-step">
            <div class="step-num num-blue">1</div>
            <div class="step-text"><strong>Registrate o iniciá sesión</strong>Creá tu cuenta con nombre, email y DNI en menos de 2 minutos.</div>
          </div>
          <div class="how-step">
            <div class="step-num num-blue">2</div>
            <div class="step-text"><strong>Elegí campaña y monto</strong>Seleccioná a qué destinar tu aporte: indumentaria, aulas o aires acondicionados.</div>
          </div>
          <div class="how-step">
            <div class="step-num num-blue">3</div>
            <div class="step-text"><strong>Pagá de forma segura</strong>Redireccionamos a MercadoPago. Tu pago está protegido en todo momento.</div>
          </div>
          <div class="how-step">
            <div class="step-num num-blue">4</div>
            <div class="step-text"><strong>Recibí tu comprobante</strong>Una vez confirmado, descargá tu recibo oficial desde tu panel.</div>
          </div>
        </div>
      </div>

      <!-- Track solicitud -->
      <div class="how-track animate-up" style="animation-delay:.12s">
        <div class="how-track-header">
          <div class="how-track-dot dot-amber">📋</div>
          <span style="color:var(--amber-soft)">Track de Solicitud</span>
        </div>
        <div class="how-steps">
          <div class="how-step" style="border-color:rgba(245,158,11,0.12)">
            <div class="step-num num-amber">1</div>
            <div class="step-text"><strong>Registrate con tu DNI</strong>Tu documento es tu constancia de identidad en el sistema.</div>
          </div>
          <div class="how-step" style="border-color:rgba(245,158,11,0.12)">
            <div class="step-num num-amber">2</div>
            <div class="step-text"><strong>Completá el formulario</strong>Elegí el ítem, talle y cantidad requerida.</div>
          </div>
          <div class="how-step" style="border-color:rgba(245,158,11,0.12)">
            <div class="step-num num-amber">3</div>
            <div class="step-text"><strong>Revisión por administración</strong>El equipo verifica tu solicitud. Seguí el estado en tiempo real.</div>
          </div>
          <div class="how-step" style="border-color:rgba(245,158,11,0.12)">
            <div class="step-num num-amber">4</div>
            <div class="step-text"><strong>Aceptada o con observaciones</strong>Si es aprobada, coordinás el retiro. Si hay observaciones, te informamos.</div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ═══════════════ CAMPAÑAS ═══════════════ -->
<section class="campaigns-section" id="campañas">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow">Nuestras Campañas</div>
      <h2 class="section-title">¿A qué va tu aporte?</h2>
      <p class="section-desc">Cada peso donado tiene un destino concreto. Conocé nuestras campañas activas.</p>
    </div>
    <div class="campaigns-grid">
      <?php foreach ($parentCamps as $i => $c):
        $cpct   = $c['goal_amount'] > 0 ? min(100, round(($c['current_amount'] / $c['goal_amount']) * 100, 1)) : 0;
        $mySubs = $subCamps[$c['id']] ?? [];
      ?>
      <div class="campaign-card animate-up" style="animation-delay:<?= $i * .12 ?>s">

        <?php if ($c['image']): ?>
          <img src="<?= h($c['image']) ?>" alt="<?= h($c['name']) ?>" class="campaign-img">
        <?php else: ?>
          <div class="campaign-img-placeholder"><?= h($c['emoji'] ?? '🎯') ?></div>
        <?php endif ?>

        <div class="campaign-body">
          <h3 class="campaign-name"><?= h($c['name']) ?></h3>
          <p class="campaign-desc"><?= h($c['description']) ?></p>

          <div class="campaign-progress">
            <div class="cp-labels">
              <span class="cp-raised"><?= money((float)$c['current_amount']) ?></span>
              <span class="cp-goal">de <?= money((float)$c['goal_amount']) ?></span>
            </div>
            <div class="cp-bar"><div class="cp-fill" style="width:<?= $cpct ?>%"></div></div>
            <div class="cp-pct"><?= $cpct ?>% completado</div>
          </div>

          <?php if ($user): ?>
            <a href="donate.php?campaign=<?= $c['id'] ?>" class="btn-campaign" style="margin-top:.75rem">Donar a esta campaña</a>
          <?php else: ?>
            <a href="register.php" class="btn-campaign" style="margin-top:.75rem">Registrarte para donar</a>
          <?php endif ?>

          <?php if (!empty($mySubs)): ?>
          <div class="sub-campaigns-wrap">
            <button class="sub-toggle-btn" onclick="toggleSubs(<?= $c['id'] ?>)" type="button">
              <span class="sub-toggle-icon" id="si-<?= $c['id'] ?>">▾</span>
              Ver <?= count($mySubs) ?> sub-campaña<?= count($mySubs) > 1 ? 's' : '' ?>
            </button>
            <div class="sub-list" id="sl-<?= $c['id'] ?>">
              <?php foreach ($mySubs as $s):
                $sp = $s['goal_amount'] > 0 ? min(100, round(($s['current_amount'] / $s['goal_amount']) * 100, 1)) : 0;
              ?>
              <div class="sub-item">
                <?php if ($s['image']): ?>
                  <img src="<?= h($s['image']) ?>" alt="" class="sub-item-img">
                <?php else: ?>
                  <div class="sub-item-emoji"><?= h($s['emoji'] ?? '🎯') ?></div>
                <?php endif ?>
                <div class="sub-item-info">
                  <div class="sub-item-name"><?= h($s['name']) ?></div>
                  <div class="sub-item-prog"><?= money((float)$s['current_amount']) ?> / <?= money((float)$s['goal_amount']) ?></div>
                  <div class="sub-item-bar"><div class="sub-item-bar-fill" style="width:<?= $sp ?>%"></div></div>
                </div>
                <?php if ($user): ?>
                  <a href="donate.php?campaign=<?= $s['id'] ?>" class="btn-campaign sub-donate-btn">Donar</a>
                <?php else: ?>
                  <a href="register.php" class="btn-campaign sub-donate-btn">Donar</a>
                <?php endif ?>
              </div>
              <?php endforeach ?>
            </div>
          </div>
          <?php endif ?>
        </div>
      </div>
      <?php endforeach ?>
    </div>
  </div>
</section>

<!-- ═══════════════ NOSOTROS / MISIÓN ═══════════════ -->
<section class="about-section" id="nosotros">
  <div class="container">
    <div class="about-grid">
      <div class="about-text animate-up">
        <div class="section-eyebrow">Nuestra Misión</div>
        <h2 class="section-title">Apoyando a quienes construyen el futuro</h2>
        <p style="color:var(--text-secondary); font-size:0.95rem; line-height:1.75; margin-bottom:1.5rem">
          La Cooperativa de la Escuela Fray Luis Beltrán trabaja incansablemente para brindar a los estudiantes las condiciones y herramientas que merecen. Tu aporte transforma realidades.
        </p>
        <div class="about-items">
          <div class="about-item">
            <div class="about-icon">👔</div>
            <div><strong>Indumentaria</strong><small>Ropa de grafa, botas de seguridad y chombas institucionales</small></div>
          </div>
          <div class="about-item">
            <div class="about-icon">🏗️</div>
            <div><strong>Infraestructura</strong><small>Reparación de aulas, ventanas y mobiliario escolar</small></div>
          </div>
          <div class="about-item">
            <div class="about-icon">🌡️</div>
            <div><strong>Confort</strong><small>Instalación de aires acondicionados para un mejor aprendizaje</small></div>
          </div>
        </div>
      </div>

      <div class="about-visual animate-up" style="animation-delay:.15s">
        <div class="quote-card">
          <div class="quote-mark">"</div>
          <p class="quote-text">Un estudiante con las herramientas adecuadas es un estudiante que puede alcanzar todo su potencial. Gracias por ser parte de este cambio.</p>
          <div class="quote-author">— Cooperativa FLB</div>
        </div>
      </div>
    </div>

    <!-- ── EQUIPO ── -->
    <div class="nos-team-wrap">
      <?php foreach ($sections as $key => $sec):
        $group = array_filter($team, fn($m) => $m['section'] === $key);
        if (empty($group)) continue;
      ?>
      <div class="nos-block animate-up">
        <div class="nos-block-header">
          <span class="nos-block-icon"><?= $sec['icon'] ?></span>
          <div>
            <div class="nos-block-title"><?= $sec['title'] ?></div>
            <?php if ($sec['subtitle']): ?><div class="nos-block-sub"><?= $sec['subtitle'] ?></div><?php endif ?>
          </div>
        </div>
        <div class="nos-grid">
          <?php foreach ($group as $m): ?>
          <div class="nos-card">
            <div class="nos-av">
              <?php if (!empty($m['image'])): ?>
                <img src="<?= h($m['image']) ?>" alt="<?= h($m['name']) ?>">
              <?php else: ?>
                <div class="nos-av-init ic-<?= h($m['area']) ?>"><?= h($m['init_text'] ?? mb_substr($m['name'], 0, 1)) ?></div>
              <?php endif ?>
            </div>
            <div class="nos-name"><?= h($m['name']) ?></div>
            <div class="nos-role"><?= h($m['role']) ?></div>
            <span class="nos-area area-<?= h($m['area']) ?>">
              <?= $m['area'] == 'info' ? 'Informática' : ($m['area'] == 'elec' ? 'Electromecánica' : ($m['area'] == 'auto' ? 'Automotriz' : 'Directivo')) ?>
            </span>
          </div>
          <?php endforeach ?>
        </div>
      </div>
      <?php endforeach ?>
    </div>

  </div>
</section>

<!-- ═══════════════ CTA ═══════════════ -->
<section class="cta-section">
  <div class="cta-glow"></div>
  <h2 class="cta-title">¿Listo para<br><span class="gradient-text">participar?</span></h2>
  <p class="cta-desc">Doná para apoyar a la institución, o si sos alumno/a, solicitá tu uniforme de forma sencilla.</p>
  <div class="cta-buttons">
    <?php if ($user): ?>
      <a href="donate.php"             class="btn btn-hero">💜 Donar Ahora</a>
      <a href="solicitar.php"          class="btn btn-amber">📋 Solicitar Uniforme</a>
      <a href="solicitar-servicio.php" class="btn btn-cyan">🔧 Servicios de Taller</a>
    <?php else: ?>
      <a href="register.php" class="btn btn-hero">✦ Registrarse</a>
      <a href="login.php"    class="btn btn-hero-outline">Ya tengo cuenta</a>
    <?php endif ?>
  </div>
</section>

<!-- ═══════════════ FOOTER ═══════════════ -->
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <img src="assets/logo.png" alt="FLB">
      <div>
        <div class="footer-name">Cooperativa FLB</div>
        <div class="footer-sub">Fray Luis Beltrán</div>
      </div>
    </div>
    <div class="footer-links">
      <a href="index.php">Inicio</a>
      <a href="#campañas">Campañas</a>
      <a href="nosotros.php">Nosotros</a>
      <a href="comisiones.php">Comisiones</a>
      <a href="login.php">Ingresar</a>
      <a href="register.php">Registrarse</a>
    </div>
    <div class="footer-copy">© <?= date('Y') ?> Cooperativa Fray Luis Beltrán. Todos los derechos reservados.</div>
  </div>
</footer>

<!-- ═══════════════ SCRIPTS ═══════════════ -->
<script src="assets/app.js"></script>

<!-- Three.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

<script>
/* ══════════════════════════════════════════
   AURORA 3D — WebGL / Three.js
   Animated aurora borealis background with
   mouse parallax and floating particles
══════════════════════════════════════════ */
(function() {
  'use strict';

  /* ── Three.js Aurora ── */
  var canvas  = document.getElementById('aurora-canvas');
  var W = window.innerWidth, H = window.innerHeight;

  var renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: false });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
  renderer.setSize(W, H);
  renderer.setClearColor(0x000000, 0);

  var scene  = new THREE.Scene();
  var camera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);

  /* Shader material — aurora fragment shader */
  var vertSrc = [
    'varying vec2 vUv;',
    'void main() {',
    '  vUv = uv;',
    '  gl_Position = vec4(position, 1.0);',
    '}'
  ].join('\n');

  var fragSrc = [
    'precision mediump float;',
    'uniform float uTime;',
    'uniform vec2  uMouse;',
    'uniform vec2  uRes;',
    'varying vec2 vUv;',

    'float hash(vec2 p) {',
    '  p = fract(p * vec2(234.34, 435.345));',
    '  p += dot(p, p + 34.23);',
    '  return fract(p.x * p.y);',
    '}',

    'float noise(vec2 p) {',
    '  vec2 i = floor(p); vec2 f = fract(p);',
    '  float a = hash(i); float b = hash(i+vec2(1,0));',
    '  float c = hash(i+vec2(0,1)); float d = hash(i+vec2(1,1));',
    '  vec2 u = f*f*(3.0-2.0*f);',
    '  return mix(a,b,u.x) + (c-a)*u.y*(1.0-u.x) + (d-b)*u.x*u.y;',
    '}',

    'float fbm(vec2 p) {',
    '  float v=0.0, a=0.5;',
    '  for(int i=0;i<5;i++) { v+=a*noise(p); p*=2.0; a*=0.5; }',
    '  return v;',
    '}',

    'void main() {',
    '  vec2 uv = vUv;',
    '  vec2 mp = uMouse * 0.08;',

    /* aurora bands */
    '  float t = uTime * 0.18;',
    '  vec2 q = vec2(fbm(uv + t*0.3 + mp), fbm(uv + vec2(1.7,9.2)));',
    '  vec2 r = vec2(fbm(uv + 4.0*q + vec2(1.7,9.2) + t*0.15), fbm(uv + 4.0*q + vec2(8.3,2.8)));',
    '  float f = fbm(uv + 4.0*r);',

    /* colour palette: deep blue → cyan → purple */
    '  vec3 col = mix(',
    '    vec3(0.02, 0.04, 0.18),',
    '    mix(',
    '      vec3(0.06, 0.25, 0.55),',
    '      mix(vec3(0.04, 0.6, 0.85), vec3(0.42, 0.14, 0.72), clamp(f*f*4.0,0.0,1.0)),',
    '      clamp(f*2.0,0.0,1.0)',
    '    ),',
    '    clamp((f*f+0.5)*1.0, 0.0, 1.0)',
    '  );',

    /* vignette */
    '  float vg = 1.0 - smoothstep(0.3, 0.95, length(uv - 0.5) * 1.4);',
    '  col *= vg;',

    /* top fade — aurora lives at top */
    '  float topFade = smoothstep(0.0, 0.4, uv.y);',
    '  col = mix(col * 0.15, col, (1.0 - topFade * 0.6));',

    /* overall brightness cap */
    '  col = clamp(col * 1.2, 0.0, 0.6);',

    '  gl_FragColor = vec4(col, 0.9);',
    '}'
  ].join('\n');

  var uniforms = {
    uTime:  { value: 0.0 },
    uMouse: { value: new THREE.Vector2(0, 0) },
    uRes:   { value: new THREE.Vector2(W, H) }
  };

  var geo = new THREE.PlaneGeometry(2, 2);
  var mat = new THREE.ShaderMaterial({
    vertexShader: vertSrc,
    fragmentShader: fragSrc,
    uniforms: uniforms,
    transparent: true,
    depthTest: false
  });
  scene.add(new THREE.Mesh(geo, mat));

  /* ── Particles ── */
  var pCount = 80;
  var pGeo   = new THREE.BufferGeometry();
  var pPos   = new Float32Array(pCount * 3);
  for (var i = 0; i < pCount; i++) {
    pPos[i*3]   = (Math.random() - 0.5) * 2;
    pPos[i*3+1] = (Math.random() - 0.5) * 2;
    pPos[i*3+2] = 0;
  }
  pGeo.setAttribute('position', new THREE.BufferAttribute(pPos, 3));
  var pMat = new THREE.PointsMaterial({
    color: 0x88ddff, size: 0.004, transparent: true, opacity: 0.55
  });
  var particles = new THREE.Points(pGeo, pMat);
  scene.add(particles);

  /* ── Mouse parallax ── */
  var mouseX = 0, mouseY = 0;
  document.addEventListener('mousemove', function(e) {
    mouseX = (e.clientX / window.innerWidth  - 0.5) * 2;
    mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
  });

  /* ── Resize ── */
  window.addEventListener('resize', function() {
    W = window.innerWidth; H = window.innerHeight;
    renderer.setSize(W, H);
    uniforms.uRes.value.set(W, H);
  });

  /* ── RAF loop ── */
  var clock = new THREE.Clock();
  function animate() {
    requestAnimationFrame(animate);
    var t = clock.getElapsedTime();
    uniforms.uTime.value = t;
    uniforms.uMouse.value.x += (mouseX - uniforms.uMouse.value.x) * 0.04;
    uniforms.uMouse.value.y += (mouseY - uniforms.uMouse.value.y) * 0.04;
    /* Float particles */
    var pos = particles.geometry.attributes.position.array;
    for (var i = 0; i < pCount; i++) {
      pos[i*3+1] += 0.0003;
      if (pos[i*3+1] > 1) pos[i*3+1] = -1;
    }
    particles.geometry.attributes.position.needsUpdate = true;
    renderer.render(scene, camera);
  }
  animate();
})();

/* ══════════════════════════════════════════
   KINETIC TYPOGRAPHY — stagger on scroll
══════════════════════════════════════════ */
(function() {
  'use strict';

  /* Navbar scroll */
  var navbar = document.getElementById('navbar');
  if (navbar) {
    window.addEventListener('scroll', function() {
      navbar.classList.toggle('scrolled', window.pageYOffset > 40);
    }, { passive: true });
  }

  /* Hamburger */
  var hamburger  = document.getElementById('hamburger');
  var mobileMenu = document.getElementById('mobileMenu');
  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', function() {
      mobileMenu.classList.toggle('open');
    });
    document.addEventListener('click', function(e) {
      if (mobileMenu.classList.contains('open') &&
          !hamburger.contains(e.target) &&
          !mobileMenu.contains(e.target)) {
        mobileMenu.classList.remove('open');
      }
    });
  }

  /* Animate counters */
  function easeOut(t) { return 1 - Math.pow(1-t, 3); }
  function formatMoney(v) { return '$' + Math.round(v).toLocaleString('es-AR'); }

  function animateCounter(el) {
    var target   = parseFloat(el.getAttribute('data-target')) || 0;
    var fmt      = el.getAttribute('data-format') || '';
    var duration = 1800;
    var start    = null;
    function tick(now) {
      if (!start) start = now;
      var progress = Math.min((now - start) / duration, 1);
      var val      = target * easeOut(progress);
      el.textContent = fmt === 'money' ? formatMoney(val) : Math.round(val).toLocaleString('es-AR');
      if (progress < 1) requestAnimationFrame(tick);
      else el.textContent = fmt === 'money' ? formatMoney(target) : Math.round(target).toLocaleString('es-AR');
    }
    requestAnimationFrame(tick);
  }

  /* Progress bar fill on load */
  setTimeout(function() {
    var fills = document.querySelectorAll('.progress-bar-fill');
    for (var i = 0; i < fills.length; i++) {
      var pct = fills[i].style.getPropertyValue('--pct') || '0%';
      fills[i].style.width = pct;
    }
    var cpFills = document.querySelectorAll('.cp-fill');
    for (var j = 0; j < cpFills.length; j++) {
      var w = cpFills[j].style.width;
      cpFills[j].style.width = '0%';
      (function(el, fw) {
        setTimeout(function() { el.style.width = fw; }, 100);
      })(cpFills[j], w);
    }
  }, 400);

  /* IntersectionObserver for scroll animations */
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          var el = entry.target;
          el.classList.add('in-view');
          /* Trigger counters when stat cards appear */
          var counters = el.querySelectorAll('.counter');
          counters.forEach(animateCounter);
          io.unobserve(el);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    var animated = document.querySelectorAll('.animate-up');
    animated.forEach(function(el, i) {
      if (!el.style.transitionDelay) {
        el.style.transitionDelay = (i * 0.04) + 's';
      }
      io.observe(el);
    });

    /* Also trigger hero counters immediately */
    var heroCounters = document.querySelectorAll('.hero-progress .counter');
    setTimeout(function() {
      heroCounters.forEach(animateCounter);
    }, 1300);

  } else {
    /* Fallback */
    document.querySelectorAll('.animate-up').forEach(function(el) {
      el.classList.add('in-view');
    });
    document.querySelectorAll('.counter').forEach(animateCounter);
  }
})();

/* Sub-campaign toggle */
function toggleSubs(id) {
  var list = document.getElementById('sl-' + id);
  var icon = document.getElementById('si-' + id);
  if (!list) return;
  list.classList.toggle('open');
  icon.style.transform = list.classList.contains('open') ? 'rotate(180deg)' : '';
}
</script>
</body>
</html>