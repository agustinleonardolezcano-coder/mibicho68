<?php
ob_start();
require_once __DIR__ . '/auth.php';
$token = trim($_GET['token'] ?? '');
$user  = $token ? dbFetch('SELECT * FROM users WHERE reset_token=? AND reset_expires > NOW()', [$token]) : null;

$error = ''; $success = false;
if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $error = 'Token inválido.'; }
    else {
        $pass  = $_POST['password'] ?? '';
        $pass2 = $_POST['password2'] ?? '';
        if (strlen($pass) < 8) $error = 'La contraseña debe tener al menos 8 caracteres.';
        elseif ($pass !== $pass2) $error = 'Las contraseñas no coinciden.';
        else {
            dbUpdate('users', [
                'password'      => password_hash($pass, PASSWORD_DEFAULT),
                'reset_token'   => null,
                'reset_expires' => null,
            ], 'id=?', [$user['id']]);
            $success = true;
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
<title>Nueva Contraseña — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="dark auth-page">
<div class="auth-bg"><div class="auth-glow-1"></div><div class="auth-glow-2"></div></div>
<div class="auth-container">
  <div class="auth-card animate-in">
    <a href="index.php" class="auth-brand">
      <img src="assets/logo.png" alt="FLB" class="auth-logo">
      <div><div class="auth-brand-main">COOPERATIVA</div><div class="auth-brand-sub">Fray Luis Beltrán</div></div>
    </a>
    <h1 class="auth-title">Nueva Contraseña</h1>
    <?php
ob_start(); if (!$user): ?>
      <div class="alert alert-error"><span>⚠</span>El enlace es inválido o ya expiró.</div>
      <a href="forgot-password.php" class="btn btn-primary btn-full" style="margin-top:1rem">Solicitar nuevo enlace</a>
    <?php
ob_start(); elseif ($success): ?>
      <div class="alert alert-success"><span>✅</span>Tu contraseña fue actualizada exitosamente.</div>
      <a href="login.php" class="btn btn-primary btn-full" style="margin-top:1rem">Ingresar</a>
    <?php
ob_start(); else: ?>
      <?php
ob_start(); if ($error): ?><div class="alert alert-error"><span>⚠</span><?= h($error) ?></div><?php endif ?>
      <form method="post" class="auth-form">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Nueva contraseña</label>
          <div class="input-wrap">
            <input type="password" name="password" id="p1" class="form-input" placeholder="Mínimo 8 caracteres" required minlength="8">
            <button type="button" class="pass-toggle" onclick="togglePass('p1',this)">👁</button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirmar contraseña</label>
          <div class="input-wrap">
            <input type="password" name="password2" id="p2" class="form-input" placeholder="Repetí la contraseña" required>
            <button type="button" class="pass-toggle" onclick="togglePass('p2',this)">👁</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Guardar Contraseña</button>
      </form>
    <?php
ob_start(); endif ?>
  </div>
</div>
</body></html>
