<?php
ob_start();
require_once __DIR__ . '/auth.php';
if (isLoggedIn()) redirect('/dashboard.php');

$error = ''; $success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $error = 'Token inválido.'; }
    else {
        $name  = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $dni   = trim($_POST['dni'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $pass2 = $_POST['password2'] ?? '';

        if (!$name || !$email || !$dni || !$pass)
            $error = 'Completá todos los campos obligatorios.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $error = 'El email no tiene un formato válido.';
        elseif (strlen($pass) < 8)
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        elseif ($pass !== $pass2)
            $error = 'Las contraseñas no coinciden.';
        elseif (dbFetch('SELECT id FROM users WHERE email=?', [$email]))
            $error = 'Ya existe una cuenta con ese email.';
        else {
            dbInsert('users', [
                'name'       => $name,
                'email'      => $email,
                'password'   => password_hash($pass, PASSWORD_DEFAULT),
                'dni'        => $dni,
                'phone'      => $phone,
                'role'       => 'donor',
                'status'     => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
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
<title>Registro — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Raleway:wght@300;400;600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="dark auth-page">
<div class="auth-bg"><div class="auth-glow-1"></div><div class="auth-glow-2"></div></div>
<div class="auth-container">
  <div class="auth-card auth-card-wide animate-in">
    <a href="index.php" class="auth-brand">
      <img src="assets/logo.png" alt="FLB" class="auth-logo">
      <div><div class="auth-brand-main">COOPERATIVA</div><div class="auth-brand-sub">Fray Luis Beltrán</div></div>
    </a>
    <h1 class="auth-title">Crear Cuenta</h1>
    <p class="auth-subtitle">Registrate para poder realizar donaciones. Tu cuenta será revisada por un administrador.</p>

    <?php
ob_start(); if ($success): ?>
      <div class="alert alert-success" style="margin-bottom:1rem">
        <span>✅</span>
        <div><strong>¡Registro enviado!</strong><br>Tu cuenta está pendiente de aprobación. Te notificaremos cuando sea revisada.</div>
      </div>
      <a href="login.php" class="btn btn-primary btn-full">Ir al Login</a>
    <?php
ob_start(); else: ?>
      <?php
ob_start(); if ($error): ?><div class="alert alert-error"><span>⚠</span><?= h($error) ?></div><?php endif ?>
      <form method="post" class="auth-form">
        <?= csrfField() ?>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Nombre completo *</label>
            <input type="text" name="name" class="form-input" placeholder="Juan Pérez"
                   value="<?= h($_POST['name'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">DNI *</label>
            <input type="text" name="dni" class="form-input" placeholder="12345678"
                   value="<?= h($_POST['dni'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email *</label>
          <input type="email" name="email" class="form-input" placeholder="tu@email.com"
                 value="<?= h($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Teléfono (opcional)</label>
          <input type="tel" name="phone" class="form-input" placeholder="+54 9 299 123 4567"
                 value="<?= h($_POST['phone'] ?? '') ?>">
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Contraseña *</label>
            <div class="input-wrap">
              <input type="password" name="password" id="pass1" class="form-input" placeholder="Mínimo 8 caracteres" required minlength="8">
              <button type="button" class="pass-toggle" onclick="togglePass('pass1',this)">👁</button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar contraseña *</label>
            <div class="input-wrap">
              <input type="password" name="password2" id="pass2" class="form-input" placeholder="Repetí la contraseña" required>
              <button type="button" class="pass-toggle" onclick="togglePass('pass2',this)">👁</button>
            </div>
          </div>
        </div>
        <div class="form-notice">
          <span>ℹ️</span> Tu registro será revisado por un administrador antes de poder ingresar. Este proceso puede demorar hasta 24 horas.
        </div>
        <button type="submit" class="btn btn-primary btn-full">Enviar Registro</button>
      </form>
      <div class="auth-footer">
        ¿Ya tenés cuenta? <a href="login.php" class="link-accent">Ingresar</a>
      </div>
    <?php
ob_start(); endif ?>
  </div>
</div>
<script src="assets/app.js"></script>
</body></html>
