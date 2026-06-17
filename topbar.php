<?php $admin = $admin ?? requireAdmin(); ?>
<header class="admin-topbar">
  <button class="topbar-toggle" id="sidebarToggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
    ☰
  </button>
  <div class="topbar-title"><?= SITE_NAME ?> · Admin</div>
  <div class="topbar-right">
    <span class="topbar-admin-name">👤 <?= h($admin['name']) ?></span>
    <a href="../logout.php" class="btn btn-xs btn-outline">Salir</a>
  </div>
</header>
