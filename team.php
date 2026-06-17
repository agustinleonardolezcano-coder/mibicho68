<?php
ob_start();
require_once __DIR__ . '/../functions.php';
$admin = requireAdmin();

$success = $error = '';
$edit = null;

// ====================== PROCESAR FORMULARIO ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Token inválido';
    } else {
        $id         = (int)($_POST['id'] ?? 0);
        $section    = $_POST['section'] ?? '';
        $name       = trim($_POST['name'] ?? '');
        $role       = trim($_POST['role'] ?? '');
        $area       = $_POST['area'] ?? '';
        $init_text  = trim($_POST['init_text'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        // Subida de imagen
        $imagePath = '';
        if (!empty($_FILES['image']['name'])) {
            $ext  = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $nameSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $newName = $nameSlug . '-' . time() . '.' . $ext;
            $dest = __DIR__ . '/../assets/team/' . $newName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $imagePath = 'assets/team/' . $newName;
            } else {
                $error = 'No se pudo subir la imagen';
            }
        }

        if (!$error) {
            if ($id > 0) {
                // EDITAR
                $data = [
                    'name'       => $name,
                    'role'       => $role,
                    'area'       => $area,
                    'sort_order' => $sort_order
                ];
                if ($imagePath) $data['image'] = $imagePath;
                if ($init_text) $data['init_text'] = $init_text;

                dbUpdate('team_members', $data, 'id=?', [$id]);
                $success = '✅ Miembro actualizado correctamente';
            } else {
                // AGREGAR
                dbInsert('team_members', [
                    'section'    => $section,
                    'name'       => $name,
                    'role'       => $role,
                    'area'       => $area,
                    'image'      => $imagePath ?: null,
                    'init_text'  => $init_text ?: null,
                    'sort_order' => $sort_order
                ]);
                $success = '✅ Miembro agregado correctamente';
            }
        }
    }
}

// ====================== ELIMINAR ======================
if (isset($_GET['delete'])) {
    dbQuery('DELETE FROM team_members WHERE id = ?', [(int)$_GET['delete']]);
    $success = '✅ Miembro eliminado';
}

// ====================== EDITAR (cargar datos) ======================
if (isset($_GET['edit'])) {
    try {
        $edit = dbFetch('SELECT * FROM team_members WHERE id = ?', [(int)$_GET['edit']]);
    } catch (Exception $e) {
        $edit = null;
    }
}

// ====================== LISTADO ======================
try {
    $members = dbFetchAll('SELECT * FROM team_members ORDER BY section, sort_order ASC');
} catch (Exception $e) {
    $members = [];
    $error = '⚠️ La tabla team_members no existe aún. Ejecutá migration_team.sql en phpMyAdmin.';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#07050F">
    <title>Gestionar Equipo — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Raleway:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="dark admin-layout">

<?php include 'partials/sidebar.php'; ?>

<div class="admin-main">
    <?php include 'partials/topbar.php'; ?>

    <div class="admin-content">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">👥 Gestionar Equipo (Nosotros)</h1>
                <p class="admin-page-sub">Agrega, edita o elimina miembros del equipo</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <div class="admin-section-card">
            <h2><?= $edit ? 'Editar miembro' : 'Agregar nuevo miembro' ?></h2>
            <form method="post" enctype="multipart/form-data">
                <?= csrfField() ?>
                <?php if ($edit): ?>
                    <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                <?php endif; ?>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Sección</label>
                        <select name="section" class="form-input" required>
                            <option value="consejo_autoridades" <?= ($edit && $edit['section']=='consejo_autoridades') ? 'selected' : '' ?>>Consejo de Administración - Autoridades</option>
                            <option value="consejo_sindicos" <?= ($edit && $edit['section']=='consejo_sindicos') ? 'selected' : '' ?>>Consejo de Administración - Síndicos</option>
                            <option value="junta_escrutadora" <?= ($edit && $edit['section']=='junta_escrutadora') ? 'selected' : '' ?>>Junta Escrutadora</option>
                            <option value="junta_revisora" <?= ($edit && $edit['section']=='junta_revisora') ? 'selected' : '' ?>>Junta Revisora de Cuentas</option>
                            <option value="docentes_asesores" <?= ($edit && $edit['section']=='docentes_asesores') ? 'selected' : '' ?>>Docentes Asesores</option>
                            <option value="vocalistas" <?= ($edit && $edit['section']=='vocalistas') ? 'selected' : '' ?>>Vocalistas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Orden (dentro de la sección)</label>
                        <input type="number" name="sort_order" class="form-input" value="<?= $edit['sort_order'] ?? '0' ?>">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Nombre completo</label>
                        <input type="text" name="name" class="form-input" value="<?= h($edit['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Cargo / Rol</label>
                        <input type="text" name="role" class="form-input" value="<?= h($edit['role'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Área</label>
                    <select name="area" class="form-input" required>
                        <option value="info" <?= ($edit && $edit['area']=='info') ? 'selected' : '' ?>>Informática</option>
                        <option value="elec" <?= ($edit && $edit['area']=='elec') ? 'selected' : '' ?>>Electromecánica</option>
                        <option value="auto" <?= ($edit && $edit['area']=='auto') ? 'selected' : '' ?>>Automotriz</option>
                        <option value="doc"  <?= ($edit && $edit['area']=='doc')  ? 'selected' : '' ?>>Directivo / Docente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Foto (opcional)</label>
                    <input type="file" name="image" accept="image/*" class="form-input">
                    <?php if ($edit && $edit['image']): ?>
                        <p style="margin-top:8px">
                            <img src="../<?= h($edit['image']) ?>" style="max-height:90px;border-radius:50%;border:2px solid #7C3AED">
                        </p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Iniciales (solo si no hay foto)</label>
                    <input type="text" name="init_text" class="form-input" maxlength="10" value="<?= h($edit['init_text'] ?? '') ?>" placeholder="Ej: GS">
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    <?= $edit ? '💾 Guardar cambios' : '➕ Agregar miembro' ?>
                </button>
            </form>
        </div>

        <!-- LISTADO -->
        <div class="admin-section-card" style="margin-top:2rem">
            <h2>Miembros actuales del equipo (<?= count($members) ?>)</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Sección</th>
                            <th>Nombre</th>
                            <th>Rol</th>
                            <th>Área</th>
                            <th>Foto</th>
                            <th>Orden</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><?= str_replace('_', ' ', ucfirst($m['section'])) ?></td>
                            <td><strong><?= h($m['name']) ?></strong></td>
                            <td><?= h($m['role']) ?></td>
                            <td><?= $m['area'] ?></td>
                            <td>
                                <?php if ($m['image']): ?>
                                    <img src="../<?= h($m['image']) ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid #7C3AED">
                                <?php else: ?>
                                    <div style="width:48px;height:48px;border-radius:50%;background:#7C3AED;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem">
                                        <?= h($m['init_text'] ?? '?') ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= $m['sort_order'] ?></td>
                            <td>
                                <a href="?edit=<?= $m['id'] ?>" class="btn btn-xs btn-outline">Editar</a>
                                <a href="?delete=<?= $m['id'] ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar a <?= h($m['name']) ?>?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="../assets/app.js"></script>
</body>
</html>