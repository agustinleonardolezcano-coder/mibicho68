<?php
ob_start();
require_once __DIR__ . '/auth.php';
if (isLoggedIn()) redirect('/dashboard.php');

$error = ''; $success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $error = 'Token inválido.'; }
    else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $user  = dbFetch('SELECT id FROM users WHERE email=? AND status="approved"', [$email]);
        if ($user) {
            $token   = generateToken();
            $expires = date('Y-m-d H:i:s', time() + 3600);
            dbUpdate('users', ['reset_token' => $token, 'reset_expires' => $expires], 'id=?', [$user['id']]);
            // In production, send email with: BASE_URL . '/reset-password.php?token=' . $token
            // For demo, show the link directly
            $_SESSION['reset_link'] = BASE_URL . '/reset-password.php?token=' . $token;
        }
        $success = true; // always show success to prevent enumeration
    }
}
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#07050F">
<meta name="apple-mobile-web-app-capable" content="yes">
<title>Recuperar Contraseña — <?= SITE_NAME ?></title>
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
    <h1 class="auth-title">Recuperar Contraseña</h1>
    <p class="auth-subtitle">Ingresá tu email y te enviaremos un enlace para restablecer tu contraseña.</p>

    <?php
ob_start(); if ($success): ?>
      <div class="alert alert-success">
        <span>✅</span>
        <div>Si tu email está registrado, recibirás un enlace de recuperación en breve.</div>
      </div>
      <?php
ob_start(); if (!empty($_SESSION['reset_link'])): ?>
        <div class="alert alert-info" style="margin-top:.75rem;word-break:break-all">
          <span>🔗</span>
          <div><strong>Demo (sin email configurado):</strong><br>
          <a href="<?= h($_SESSION['reset_link']) ?>" class="link-accent"><?= h($_SESSION['reset_link']) ?></a></div>
        </div>
        <?php
ob_start(); unset($_SESSION['reset_link']); ?>
      <?php
ob_start(); endif ?>
      <a href="login.php" class="btn btn-primary btn-full" style="margin-top:1.5rem">Volver al Login</a>
    <?php
ob_start(); else: ?>
      <?php
ob_start(); if ($error): ?><div class="alert alert-error"><span>⚠</span><?= h($error) ?></div><?php endif ?>
      <form method="post" class="auth-form">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Email de tu cuenta</label>
          <input type="email" name="email" class="form-input" placeholder="tu@email.com" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Enviar Enlace</button>
      </form>
      <div class="auth-footer"><a href="login.php" class="link-accent">← Volver al Login</a></div>
    <?php
ob_start(); endif ?>
  </div>
</div>
</body></html>
