<?php
ob_start();
require_once __DIR__ . '/functions.php';
$user = requireLogin();

$success = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $error = 'Token inválido.'; }
    else {
        $action = $_POST['action'] ?? '';

        if ($action === 'change_password') {
            $current = $_POST['current_password'] ?? '';
            $new     = $_POST['new_password'] ?? '';
            $new2    = $_POST['new_password2'] ?? '';
            if (!password_verify($current, $user['password'])) { $error = 'La contraseña actual es incorrecta.'; }
            elseif (strlen($new) < 8) { $error = 'La nueva contraseña debe tener al menos 8 caracteres.'; }
            elseif ($new !== $new2) { $error = 'Las contraseñas no coinciden.'; }
            else {
                dbUpdate('users', ['password' => password_hash($new, PASSWORD_DEFAULT)], 'id=?', [$user['id']]);
                $success = 'Contraseña actualizada correctamente.';
            }
        }

        if ($action === 'toggle_theme') {
            $theme = $_POST['theme'] ?? 'dark';
            dbUpdate('users', ['theme' => $theme], 'id=?', [$user['id']]);
            $_SESSION['theme'] = $theme;
            $user['theme'] = $theme;
            $success = 'Preferencia de tema guardada.';
        }
    }
}
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#07050F">
<meta name="apple-mobile-web-app-capable" content="yes">
<title>Mi Perfil — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="dark">
<nav class="navbar navbar-solid">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand">
      <img src="assets/logo.png" alt="FLB" class="nav-logo">
      <span class="brand-text"><span class="brand-main">COOPERATIVA</span><span class="brand-sub">FLB</span></span>
    </a>
    <div class="nav-links">
      <a href="dashboard.php" class="nav-link">Mi Panel</a>
      <a href="logout.php" class="nav-link">Salir</a>
    </div>
  </div>
</nav>

<main class="page-main">
  <div class="container container-sm">
    <div class="page-header animate-up">
      <div><h1 class="page-title">Mi Perfil</h1><p class="page-subtitle">Configuración de cuenta y preferencias</p></div>
    </div>

    <?php
ob_start(); if ($success): ?><div class="alert alert-success animate-up"><?= h($success) ?></div><?php endif ?>
    <?php
ob_start(); if ($error): ?><div class="alert alert-error animate-up"><?= h($error) ?></div><?php endif ?>

    <!-- Profile info -->
    <div class="section-card animate-up" style="animation-delay:.05s">
      <h3 class="section-card-title">👤 Información de Cuenta</h3>
      <div class="profile-grid">
        <div class="profile-row"><span class="profile-label">Nombre</span><span class="profile-val"><?= h($user['name']) ?></span></div>
        <div class="profile-row"><span class="profile-label">Email</span><span class="profile-val"><?= h($user['email']) ?></span></div>
        <div class="profile-row"><span class="profile-label">DNI</span><span class="profile-val"><?= h($user['dni']) ?></span></div>
        <div class="profile-row"><span class="profile-label">Teléfono</span><span class="profile-val"><?= h($user['phone'] ?: '—') ?></span></div>
        <div class="profile-row"><span class="profile-label">Rol</span><span class="profile-val"><span class="badge badge-success"><?= ucfirst($user['role']) ?></span></span></div>
        <div class="profile-row"><span class="profile-label">Miembro desde</span><span class="profile-val"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span></div>
      </div>
    </div>

    <!-- Theme toggle -->
    <div class="section-card animate-up" style="animation-delay:.1s">
      <h3 class="section-card-title">🎨 Apariencia</h3>
      <form method="post">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="toggle_theme">
        <div class="theme-toggle-wrap">
          <label class="theme-opt <?= $user['theme'] === 'dark' ? 'active' : '' ?>">
            <input type="radio" name="theme" value="dark" <?= $user['theme'] === 'dark' ? 'checked' : '' ?>>
            🌙 Modo Oscuro
          </label>
          <label class="theme-opt <?= $user['theme'] === 'light' ? 'active' : '' ?>">
            <input type="radio" name="theme" value="light" <?= $user['theme'] === 'light' ? 'checked' : '' ?>>
            ☀️ Modo Claro
          </label>
        </div>
        <button type="submit" class="btn btn-outline-sm" style="margin-top:.75rem">Guardar preferencia</button>
      </form>
    </div>

    <!-- Change password -->
    <div class="section-card animate-up" style="animation-delay:.15s">
      <h3 class="section-card-title">🔐 Cambiar Contraseña</h3>
      <form method="post">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
          <label class="form-label">Contraseña actual</label>
          <div class="input-wrap">
            <input type="password" name="current_password" id="cp0" class="form-input" required>
            <button type="button" class="pass-toggle" onclick="togglePass('cp0',this)">👁</button>
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Nueva contraseña</label>
            <div class="input-wrap">
              <input type="password" name="new_password" id="cp1" class="form-input" required minlength="8">
              <button type="button" class="pass-toggle" onclick="togglePass('cp1',this)">👁</button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar nueva contraseña</label>
            <div class="input-wrap">
              <input type="password" name="new_password2" id="cp2" class="form-input" required>
              <button type="button" class="pass-toggle" onclick="togglePass('cp2',this)">👁</button>
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
      </form>
    </div>

  </div>
</main>
<script src="assets/app.js"></script>
</body></html>
