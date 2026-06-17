<?php
$stats    = $stats ?? getStats();
$solStats = getSolicitudesStats();
$srvStats = getServiciosStats();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
function isActive(string $p, string $cd = 'admin'): string {
    global $currentPage, $currentDir;
    return ($currentPage === $p && $currentDir === $cd) ? 'active' : '';
}
?>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <img src="../assets/logo.png" alt="FLB" class="sidebar-logo">
    <div class="sidebar-brand-text">
      <div class="sidebar-brand-main">COOPERATIVA</div>
      <div class="sidebar-brand-sub">Fray Luis Beltrán</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-group-label">Principal</div>
    <a href="index.php" class="sidebar-link <?= isActive('index.php') ?>">
      <span class="sl-icon">📊</span> Dashboard
    </a>
    <a href="donations.php" class="sidebar-link <?= isActive('donations.php') ?>">
      <span class="sl-icon">💳</span> Donaciones
      <?php if (($stats['pending_dons'] ?? 0) > 0): ?>
        <span class="sl-badge"><?= $stats['pending_dons'] ?></span>
      <?php endif ?>
    </a>
    <a href="solicitudes.php" class="sidebar-link <?= isActive('solicitudes.php') ?>" style="color:<?= isActive('solicitudes.php')?'#FCD34D':'inherit' ?>">
      <span class="sl-icon">📋</span> Solicitudes
      <?php if (($solStats['pending'] ?? 0) > 0): ?>
        <span class="sl-badge" style="background:#D97706;color:#fff"><?= $solStats['pending'] ?></span>
      <?php endif ?>
    </a>
    <a href="servicios.php" class="sidebar-link <?= isActive('servicios.php') ?>" style="color:<?= isActive('servicios.php')?'#93C5FD':'inherit' ?>">
      <span class="sl-icon">🔧</span> Servicios
      <?php if (($srvStats['pending'] ?? 0) > 0): ?>
        <span class="sl-badge" style="background:#1565C0;color:#fff"><?= $srvStats['pending'] ?></span>
      <?php endif ?>
    </a>
    <a href="campaigns.php" class="sidebar-link <?= isActive('campaigns.php') ?>">
      <span class="sl-icon">🎯</span> Campañas
    </a>

    <div class="nav-group-label" style="margin-top:.75rem">Usuarios</div>
    <a href="users.php" class="sidebar-link <?= isActive('users.php') ?>">
      <span class="sl-icon">👥</span> Donantes
      <?php if (($stats['pending_users'] ?? 0) > 0): ?>
        <span class="sl-badge"><?= $stats['pending_users'] ?></span>
      <?php endif ?>
    </a>
    <a href="admins.php" class="sidebar-link <?= isActive('admins.php') ?>">
      <span class="sl-icon">🔐</span> Administradores
    </a>

    <div class="nav-group-label" style="margin-top:.75rem">Sistema</div>
    <a href="comisiones.php" class="sidebar-link <?= isActive('comisiones.php') ?>">
      <span class="sl-icon">💳</span> Cuotas y Tarifas
    </a>
    <a href="team.php" class="sidebar-link <?= isActive('team.php') ?>">
      <span class="sl-icon">🏛️</span> Equipo / Comisión
    </a>
    <a href="export.php" class="sidebar-link <?= isActive('export.php') ?>">
      <span class="sl-icon">📥</span> Exportar CSV
    </a>
    <a href="settings.php" class="sidebar-link <?= isActive('settings.php') ?>">
      <span class="sl-icon">⚙️</span> Configuración
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="../index.php" class="sidebar-link"><span class="sl-icon">🌐</span> Ver sitio</a>
    <a href="../logout.php" class="sidebar-link logout"><span class="sl-icon">🚪</span> Salir</a>
  </div>
</aside>
