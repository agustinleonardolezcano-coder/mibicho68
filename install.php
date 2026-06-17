<?php
ob_start();
require_once __DIR__ . '/config.php';

$done = false; $error = '';

// Check if already installed
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $exists = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($exists && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
} catch (Exception $e) {
    $error = 'No se puede conectar a la base de datos: ' . $e->getMessage();
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminName  = trim($_POST['admin_name'] ?? '');
    $adminEmail = strtolower(trim($_POST['admin_email'] ?? ''));
    $adminPass  = $_POST['admin_pass'] ?? '';
    if (!$adminName || !$adminEmail || strlen($adminPass) < 8) {
        $error = 'Complete todos los campos. La contraseña debe tener al menos 8 caracteres.';
    } else {
        try {
            $sql = file_get_contents(__DIR__ . '/install.sql');
            foreach (explode(';', $sql) as $q) {
                $q = trim($q);
                if ($q) $pdo->exec($q);
            }
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (name,email,password,dni,phone,role,status,created_at) VALUES (?,?,?,"00000000","",  "admin","approved",NOW())')
                ->execute([$adminName, $adminEmail, $hash]);
            $done = true;
        } catch (Exception $e) {
            $error = 'Error durante la instalación: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalación — Cooperativa FLB</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:Raleway,sans-serif;background:#07050F;color:#EDE9F6;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
  .card{background:#110D20;border:1px solid #2D1B69;border-radius:16px;padding:2.5rem;width:100%;max-width:480px;box-shadow:0 0 40px rgba(91,45,142,.3)}
  h1{font-size:1.6rem;margin-bottom:.5rem;color:#A78BFA}
  p{color:#9B8EBA;margin-bottom:1.5rem;font-size:.95rem}
  label{display:block;margin-bottom:.4rem;font-size:.9rem;color:#C4B5FD}
  input{width:100%;background:#1A1430;border:1px solid #2D1B69;border-radius:8px;padding:.75rem 1rem;color:#EDE9F6;font-size:1rem;margin-bottom:1rem;outline:none;transition:border-color .2s}
  input:focus{border-color:#7C3AED}
  button{width:100%;background:linear-gradient(135deg,#5B2D8E,#7C3AED);border:none;border-radius:8px;padding:.9rem;color:#fff;font-size:1rem;font-weight:700;cursor:pointer;margin-top:.5rem}
  .error{background:#3D1A1A;border:1px solid #9B1C1C;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;color:#FCA5A5;font-size:.9rem}
  .success{background:#1A3D2E;border:1px solid #166534;border-radius:8px;padding:1rem;color:#86EFAC;font-size:.95rem;text-align:center}
  .success a{color:#A78BFA;font-weight:700}
</style>
</head>
<body>
<div class="card">
  <h1>⚙️ Instalación del Sistema</h1>
  <p>Cooperativa Fray Luis Beltrán — Configuración inicial</p>
  <?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif ?>
  <?php if ($done): ?>
    <div class="success">
      ✅ Instalación completa.<br><br>
      <strong>Por seguridad, eliminá el archivo <code>install.php</code> del servidor.</strong><br><br>
      <a href="<?= BASE_URL ?>/login.php">→ Ir al login</a>
    </div>
  <?php else: ?>
  <form method="post">
    <label>Nombre del administrador</label>
    <input type="text" name="admin_name" placeholder="Ej: Laura García" required>
    <label>Email del administrador</label>
    <input type="email" name="admin_email" placeholder="admin@flb.edu.ar" required>
    <label>Contraseña (mínimo 8 caracteres)</label>
    <input type="password" name="admin_pass" placeholder="Contraseña segura" required minlength="8">
    <button type="submit">Instalar Sistema</button>
  </form>
  <?php endif ?>
</div>
</body>
</html>
