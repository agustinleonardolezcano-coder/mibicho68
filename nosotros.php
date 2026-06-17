<?php
require_once __DIR__ . '/functions.php';
$user = getCurrentUser();

// Cargar equipo desde la base de datos
try {
    $team = dbFetchAll('SELECT * FROM team_members ORDER BY section, sort_order ASC');
} catch (Exception $e) {
    $team = [];
}

$sections = [
    'consejo_autoridades' => ['title' => 'Consejo de Administración', 'subtitle' => 'Autoridades', 'icon' => '🏛️'],
    'consejo_sindicos'    => ['title' => 'Consejo de Administración', 'subtitle' => 'Síndicos Titulares', 'icon' => '🏛️'],
    'junta_escrutadora'   => ['title' => 'Junta Escrutadora de Asamblea', 'subtitle' => '4 Titulares', 'icon' => '🗳️'],
    'junta_revisora'      => ['title' => 'Junta Revisora de Cuentas', 'subtitle' => '3 Titulares', 'icon' => '📊'],
    'docentes_asesores'   => ['title' => 'Docentes Asesores', 'subtitle' => '', 'icon' => '🎓'],
    'vocalistas'          => ['title' => 'Vocalistas',         'subtitle' => '', 'icon' => '🎤'],
];
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <meta name="theme-color" content="#07050F">
    <title>Nosotros — <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Raleway:wght@300;400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">

    <style>
        /* ══ NOSOTROS PAGE ══════════════════════════════════════════ */
        body { background: #050D1F; color: #EFF6FF; }

        .nos-hero {
          padding: 7rem 1.25rem 3rem;
          text-align: center;
          background: radial-gradient(ellipse 70% 50% at 50% 0%, rgba(124,58,237,.25), transparent 70%);
        }
        .nos-hero-tag {
          display: inline-block;
          font-size: .72rem;
          font-weight: 700;
          letter-spacing: .12em;
          text-transform: uppercase;
          color: #A78BFA;
          background: rgba(124,58,237,.1);
          border: 1px solid rgba(124,58,237,.3);
          border-radius: 30px;
          padding: .3rem 1rem;
          margin-bottom: 1.25rem;
        }
        .nos-hero h1 {
          font-family: 'Cinzel', serif;
          font-size: clamp(1.6rem, 4vw, 2.8rem);
          color: #EFF6FF;
          margin-bottom: .85rem;
          line-height: 1.2;
        }
        .nos-hero p {
          color: #BFDBFE;
          max-width: 560px;
          margin: 0 auto;
          font-size: 1rem;
          line-height: 1.7;
        }

        .nos-wrap {
          max-width: 1100px;
          margin: 0 auto;
          padding: 2.5rem 1.25rem 5rem;
        }

        .nos-block {
          background: rgba(8,15,39,.7);
          border: 1px solid rgba(124,58,237,.18);
          border-radius: 22px;
          padding: 2rem 1.75rem 1.75rem;
          margin-bottom: 2rem;
          backdrop-filter: blur(8px);
        }
        .nos-block-header {
          display: flex;
          align-items: center;
          gap: 1rem;
          margin-bottom: 1.5rem;
          padding-bottom: 1rem;
          border-bottom: 1px solid rgba(124,58,237,.15);
        }
        .nos-block-icon { font-size: 2rem; line-height: 1; flex-shrink: 0; }
        .nos-block-title {
          font-family: 'Cinzel', serif;
          font-size: 1rem;
          color: #EFF6FF;
          font-weight: 700;
          margin-bottom: .15rem;
        }
        .nos-block-sub { font-size: .78rem; color: #4B6A9B; }

        .nos-sub-label {
          font-size: .68rem;
          font-weight: 700;
          letter-spacing: .1em;
          text-transform: uppercase;
          color: #7C3AED;
          margin-bottom: .9rem;
          padding-left: .1rem;
        }

        .nos-grid {
          display: flex;
          flex-wrap: wrap;
          gap: 1rem;
          margin-bottom: 1.25rem;
        }

        .nos-card {
          flex: 1 1 110px;
          max-width: 140px;
          background: rgba(5,10,28,.8);
          border: 1px solid rgba(124,58,237,.18);
          border-radius: 16px;
          padding: 1.1rem .75rem .9rem;
          text-align: center;
          transition: transform .22s, box-shadow .22s, border-color .22s;
        }
        .nos-card:hover {
          transform: translateY(-4px);
          box-shadow: 0 8px 28px rgba(124,58,237,.2);
          border-color: rgba(124,58,237,.45);
        }

        .nos-av {
          width: 56px;
          height: 56px;
          border-radius: 50%;
          overflow: hidden;
          margin: 0 auto .8rem;
          flex-shrink: 0;
          border: 2px solid rgba(124,58,237,.45);
          transition: border-color .22s;
          position: relative;
        }
        .nos-card:hover .nos-av { border-color: #7C3AED; }

        .nos-av img {
          display: block;
          width: 56px !important;
          height: 56px !important;
          max-width: none !important;
          max-height: none !important;
          object-fit: cover;
          object-position: center top;
        }

        .nos-av-init {
          width: 100%;
          height: 100%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-family: 'Cinzel', serif;
          font-size: .9rem;
          font-weight: 700;
          letter-spacing: .04em;
          user-select: none;
        }

        /* Colores por especialidad */
        .ic-info  { background: rgba(124,58,237,.15); color: #A78BFA; }
        .ic-elec  { background: rgba(21,101,192,.15); color: #60A5FA; }
        .ic-auto  { background: rgba(180,83,9,.15);   color: #FBB360; }
        .ic-doc   { background: rgba(6,95,70,.15);    color: #6EE7B7; }

        .nos-name {
          font-family: 'Cinzel', serif;
          font-size: .68rem;
          color: #EFF6FF;
          font-weight: 700;
          line-height: 1.3;
          margin-bottom: .2rem;
        }
        .nos-role {
          font-size: .65rem;
          color: #A78BFA;
          font-weight: 600;
          line-height: 1.3;
          margin-bottom: .25rem;
        }
        .nos-area {
          display: inline-block;
          font-size: .58rem;
          font-weight: 700;
          border-radius: 20px;
          padding: .1rem .45rem;
          margin-top: .15rem;
        }
        .area-info { background: rgba(124,58,237,.12); color: #A78BFA; border: 1px solid rgba(124,58,237,.25); }
        .area-elec { background: rgba(21,101,192,.12); color: #60A5FA; border: 1px solid rgba(21,101,192,.25); }
        .area-auto { background: rgba(180,83,9,.12);   color: #FBB360; border: 1px solid rgba(180,83,9,.25); }
        .area-doc  { background: rgba(6,95,70,.12);    color: #6EE7B7; border: 1px solid rgba(6,95,70,.25); }

        @media (max-width: 600px) {
          .nos-block { padding: 1.3rem .9rem 1.1rem; }
          .nos-card  { flex: 1 1 90px; max-width: 120px; padding: .9rem .55rem .75rem; }
          .nos-av, .nos-av img { width: 48px !important; height: 48px !important; }
          .nos-block-title { font-size: .88rem; }
        }
    </style>
</head>
<body>

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
      <a href="index.php#campañas" class="nav-link">Campañas</a>
      <a href="index.php#como-funciona" class="nav-link">¿Cómo funciona?</a>
      <a href="solicitar-servicio.php" class="nav-link">🔧 Servicios</a>
      <a href="nosotros.php" class="nav-link" style="color:#A78BFA">Nosotros</a>
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
<div class="nos-hero">
  <div class="nos-hero-tag">Cooperativa FLB</div>
  <h1>Nuestro Equipo</h1>
  <p>Estudiantes y docentes comprometidos con el bienestar de toda la comunidad escolar de Fray Luis Beltrán.</p>
</div>

<!-- CONTENIDO DINÁMICO -->
<div class="nos-wrap">
  <?php foreach ($sections as $key => $sec): 
    $group = array_filter($team, fn($m) => $m['section'] === $key);
    if (empty($group)) continue;
  ?>
    <div class="nos-block">
      <div class="nos-block-header">
        <span class="nos-block-icon"><?= $sec['icon'] ?></span>
        <div>
          <div class="nos-block-title"><?= $sec['title'] ?></div>
          <div class="nos-block-sub"><?= $sec['subtitle'] ?></div>
        </div>
      </div>

      <div class="nos-grid">
        <?php foreach ($group as $m): ?>
        <div class="nos-card">
          <div class="nos-av">
            <?php if (!empty($m['image'])): ?>
              <img src="<?= h($m['image']) ?>" alt="<?= h($m['name']) ?>">
            <?php else: ?>
              <div class="nos-av-init ic-<?= $m['area'] ?>"><?= h($m['init_text'] ?? substr($m['name'],0,1)) ?></div>
            <?php endif; ?>
          </div>
          <div class="nos-name"><?= h($m['name']) ?></div>
          <div class="nos-role"><?= h($m['role']) ?></div>
          <span class="nos-area area-<?= $m['area'] ?>">
            <?= $m['area']=='info' ? 'Informática' : ($m['area']=='elec' ? 'Electromecánica' : ($m['area']=='auto' ? 'Automotriz' : 'Directivo')) ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

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
      <a href="login.php">Ingresar</a>
      <a href="register.php">Registrarse</a>
    </div>
    <div class="footer-copy">© <?= date('Y') ?> Cooperativa Fray Luis Beltrán. Todos los derechos reservados.</div>
  </div>
</footer>

<script src="assets/app.js"></script>
</body>
</html>