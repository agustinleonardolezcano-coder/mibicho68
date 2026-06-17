<?php
ob_start();
require_once __DIR__ . '/auth.php';
if (isLoggedIn()) redirect('/dashboard.php');

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $error = 'Token inválido. Recargá la página.'; }
    else {
        $res = loginUser($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($res['ok']) {
            redirect($res['user']['role'] === 'admin' ? '/admin/index.php' : '/dashboard.php');
        } else {
            $error = $res['msg'];
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
<title>Ingresar — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Raleway:wght@300;400;600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="dark auth-page">
<div class="auth-bg">
  <div class="auth-glow-1"></div><div class="auth-glow-2"></div>
</div>
<div class="auth-container">
  <div class="auth-card animate-in">
    <a href="index.php" class="auth-brand">
      <img src="assets/logo.png" alt="FLB" class="auth-logo">
      <div><div class="auth-brand-main">COOPERATIVA</div><div class="auth-brand-sub">Fray Luis Beltrán</div></div>
    </a>
    <h1 class="auth-title">Bienvenido/a</h1>
    <p class="auth-subtitle">Ingresá con tu cuenta para acceder a tu panel de donaciones.</p>

    <?php
ob_start(); if ($error): ?>
      <div class="alert alert-error"><span>⚠</span><?= h($error) ?></div>
    <?php
ob_start(); endif ?>

    <form method="post" class="auth-form">
      <?= csrfField() ?>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-input" placeholder="tu@email.com"
               value="<?= h($_POST['email'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Contraseña</label>
        <div class="input-wrap">
          <input type="password" name="password" id="passInput" class="form-input" placeholder="••••••••" required>
          <button type="button" class="pass-toggle" onclick="togglePass('passInput',this)">👁</button>
        </div>
      </div>
      <div class="form-row-end">
        <a href="forgot-password.php" class="link-muted">¿Olvidaste tu contraseña?</a>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Ingresar</button>
    </form>

    <div class="auth-footer">
      ¿No tenés cuenta? <a href="register.php" class="link-accent">Registrarse</a>
    </div>
  </div>
</div>
<script src="assets/app.js"></script>
</body></html>
