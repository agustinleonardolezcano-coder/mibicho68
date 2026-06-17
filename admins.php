<?php
require_once __DIR__ . '/../functions.php';
$admin = requireAdmin();
$stats = getStats();

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) die('Token inválido.');
    $action = $_POST['action'] ?? '';

    if ($action === 'add_admin') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $name  = trim($_POST['name'] ?? '');
        $pass  = $_POST['password'] ?? '';
        if (!$email || !$name || strlen($pass) < 8) { $error = 'Completá todos los campos.'; }
        elseif (dbFetch('SELECT id FROM users WHERE email=?', [$email])) { $error = 'Ya existe una cuenta con ese email.'; }
        else {
            $id = dbInsert('users', [
                'name' => $name, 'email' => $email,
                'password' => password_hash($pass, PASSWORD_DEFAULT),
                'dni' => '00000000', 'phone' => '',
                'role' => 'admin', 'status' => 'approved',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            adminLog($admin['id'], 'add_admin', 'user', (int)$id, $name);
            $success = "Admin '$name' agregado correctamente.";
        }
    }

    if ($action === 'remove_admin') {
        $uid = (int)$_POST['user_id'];
        if ($uid === $admin['id']) { $error = 'No podés removerte a vos mismo.'; }
        else {
            dbUpdate('users', ['role' => 'donor', 'status' => 'rejected'], 'id=?', [$uid]);
            adminLog($admin['id'], 'remove_admin', 'user', $uid);
            $success = 'Administrador removido.';
        }
    }
}

$admins = dbFetchAll('SELECT * FROM users WHERE role="admin" ORDER BY created_at');
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#07050F">
<title>Administradores — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="dark admin-layout">
<?php
include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-main">
  <?php
include __DIR__ . '/partials/topbar.php'; ?>
  <div class="admin-content">
    <div class="admin-page-header">
      <div><h1 class="admin-page-title">Administradores</h1><p class="admin-page-sub">Gestión de accesos administrativos</p></div>
      <button class="btn btn-primary-sm" onclick="document.getElementById('addModal').style.display='flex'">+ Agregar Admin</button>
    </div>

    <?php if ($error): ?><div class="alert alert-error animate-up"><?= h($error) ?></div><?php endif ?>
    <?php if ($success): ?><div class="alert alert-success animate-up"><?= h($success) ?></div><?php endif ?>

    <div class="admin-section-card animate-up">
      <div class="table-responsive">
        <table class="data-table">
          <thead><tr><th>Nombre</th><th>Email</th><th>Estado</th><th>Registrado</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php foreach ($admins as $a): ?>
            <tr>
              <td>
                <strong><?= h($a['name']) ?></strong>
                <?php if ($a['id'] === $admin['id']): ?>
                  <span class="badge badge-success" style="margin-left:.5rem">Vos</span>
                <?php endif ?>
              </td>
              <td><?= h($a['email']) ?></td>
              <td><span class="badge badge-success">Activo</span></td>
              <td><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
              <td>
                <?php if ($a['id'] !== $admin['id']): ?>
                <form method="post" style="display:inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="remove_admin">
                  <input type="hidden" name="user_id" value="<?= $a['id'] ?>">
                  <button class="btn btn-xs btn-danger" onclick="return confirm('¿Quitar privilegios de admin a <?= h($a['name']) ?>?')">✗ Quitar admin</button>
                </form>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif ?>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Admin Modal -->
<div class="modal-overlay" id="addModal" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal-box">
    <h3 class="modal-title">🔐 Agregar Administrador</h3>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_admin">
      <div class="form-group">
        <label class="form-label">Nombre completo *</label>
        <input type="text" name="name" class="form-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">Contraseña inicial * (mínimo 8 caracteres)</label>
        <input type="password" name="password" class="form-input" required minlength="8">
      </div>
      <div class="form-notice"><span>⚠️</span> El nuevo administrador podrá cambiar su contraseña desde su perfil.</div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').style.display='none'">Cancelar</button>
        <button type="submit" class="btn btn-primary">Agregar Admin</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/app.js"></script>
</body></html>
