<?php
ob_start();
require_once __DIR__ . '/../functions.php';
$admin = requireAdmin();

$success = $error = '';
$edit = null;

// ══════════════════════════════════════════════════════════
// ACCIONES POST
// ══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Token de seguridad inválido. Recargá la página.';
    } else {
        $action      = $_POST['_action'] ?? '';
        $id          = (int)($_POST['id'] ?? 0);
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio      = str_replace(['.', ','], ['', '.'], trim($_POST['precio'] ?? '0'));
        $precio      = (float)$precio;
        $periodo     = $_POST['periodo'] ?? 'mensual';
        $caract      = trim($_POST['caracteristicas'] ?? '');
        $destacado   = isset($_POST['destacado']) ? 1 : 0;
        $activo      = isset($_POST['activo'])    ? 1 : 0;
        $sort_order  = (int)($_POST['sort_order'] ?? 0);

        $periodos_validos = ['mensual','trimestral','semestral','anual'];
        if (!$nombre)                              $error = 'El nombre es obligatorio.';
        elseif ($precio < 0)                       $error = 'El precio no puede ser negativo.';
        elseif (!in_array($periodo, $periodos_validos)) $error = 'Período inválido.';

        if (!$error) {
            $data = [
                'nombre'          => $nombre,
                'descripcion'     => $descripcion ?: null,
                'precio'          => $precio,
                'periodo'         => $periodo,
                'caracteristicas' => $caract ?: null,
                'destacado'       => $destacado,
                'activo'          => $activo,
                'sort_order'      => $sort_order,
            ];

            if ($action === 'edit' && $id > 0) {
                dbUpdate('comisiones', $data, 'id=?', [$id]);
                $success = '✅ Cuota actualizada correctamente.';
            } else {
                dbInsert('comisiones', $data);
                $success = '✅ Nueva cuota creada correctamente.';
            }
        }
    }
}

// ══════════════════════════════════════════════════════════
// ELIMINAR
// ══════════════════════════════════════════════════════════
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!verifyCsrf()) {
        $error = 'Token inválido.';
    } else {
        dbQuery('DELETE FROM comisiones WHERE id = ?', [(int)$_GET['delete']]);
        $success = '✅ Cuota eliminada.';
    }
}

// ══════════════════════════════════════════════════════════
// TOGGLE ACTIVO
// ══════════════════════════════════════════════════════════
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    if (!verifyCsrf()) {
        $error = 'Token inválido.';
    } else {
        $row = dbFetch('SELECT id, activo FROM comisiones WHERE id = ?', [(int)$_GET['toggle']]);
        if ($row) {
            dbUpdate('comisiones', ['activo' => $row['activo'] ? 0 : 1], 'id=?', [$row['id']]);
            $success = '✅ Visibilidad actualizada.';
        }
    }
}

// ══════════════════════════════════════════════════════════
// EDITAR — cargar datos
// ══════════════════════════════════════════════════════════
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $edit = dbFetch('SELECT * FROM comisiones WHERE id = ?', [(int)$_GET['edit']]);
    } catch (Exception $e) {
        $edit = null;
    }
}

// ══════════════════════════════════════════════════════════
// LISTAR
// ══════════════════════════════════════════════════════════
try {
    $planes = dbFetchAll('SELECT * FROM comisiones ORDER BY sort_order ASC, id ASC');
} catch (Exception $e) {
    $planes = [];
    $error = 'Error al cargar las cuotas: ' . $e->getMessage();
}

$csrf = csrfToken();

$periodoLabel = [
    'mensual'      => 'Mensual',
    'trimestral'   => 'Trimestral',
    'semestral'    => 'Semestral',
    'anual'        => 'Anual',
];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
<title>Cuotas y Tarifas — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/admin.css">
<style>
/* ── Flash messages ─────────────────────────────────────── */
.flash-success {
  background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.35);
  border-radius: 10px; padding: .75rem 1.1rem; color: #6EE7B7; margin-bottom: 1rem;
}
.flash-danger {
  background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.35);
  border-radius: 10px; padding: .75rem 1.1rem; color: #FCA5A5; margin-bottom: 1rem;
}

/* ── Layout ─────────────────────────────────────────────── */
.com-layout {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 1.5rem;
  align-items: start;
}
@media (max-width: 900px) {
  .com-layout { grid-template-columns: 1fr; }
}

/* ── Tabla de planes ─────────────────────────────────────── */
.com-table-wrap {
  background: rgba(8,15,39,.8);
  border: 1px solid rgba(124,58,237,.15);
  border-radius: 16px; overflow: hidden;
}
.com-table {
  width: 100%; border-collapse: collapse;
  font-size: .86rem;
}
.com-table thead th {
  background: rgba(124,58,237,.1);
  color: #A78BFA; font-weight: 700;
  font-size: .72rem; text-transform: uppercase;
  letter-spacing: .07em;
  padding: .75rem 1rem; text-align: left;
  border-bottom: 1px solid rgba(124,58,237,.15);
}
.com-table tbody td {
  padding: .8rem 1rem;
  border-bottom: 1px solid rgba(255,255,255,.05);
  color: #CBD5E1; vertical-align: middle;
}
.com-table tbody tr:last-child td { border-bottom: none; }
.com-table tbody tr:hover td { background: rgba(124,58,237,.05); }

.plan-name { font-weight: 700; color: #EFF6FF; }
.plan-price { font-weight: 700; color: #A78BFA; }

.badge-periodo {
  display: inline-block; font-size: .68rem; font-weight: 700;
  border-radius: 20px; padding: .18rem .6rem;
  background: rgba(124,58,237,.12);
  border: 1px solid rgba(124,58,237,.25);
  color: #A78BFA;
}
.badge-activo {
  display: inline-block; font-size: .68rem; font-weight: 700;
  border-radius: 20px; padding: .18rem .6rem;
}
.badge-on  { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.3); color: #6EE7B7; }
.badge-off { background: rgba(100,116,139,.12); border: 1px solid rgba(100,116,139,.3); color: #94A3B8; }
.badge-dest {
  display: inline-block; font-size: .65rem; font-weight: 800;
  border-radius: 20px; padding: .15rem .55rem;
  background: linear-gradient(90deg, rgba(124,58,237,.3), rgba(168,85,247,.2));
  border: 1px solid rgba(124,58,237,.4); color: #C4B5FD;
}

.com-actions { display: flex; gap: .4rem; flex-wrap: wrap; }
.btn-edit, .btn-del, .btn-toggle {
  padding: .3rem .7rem; border-radius: 7px; font-size: .76rem;
  font-weight: 700; cursor: pointer; border: none; text-decoration: none;
  transition: all .15s;
}
.btn-edit   { background: rgba(124,58,237,.15); color: #A78BFA; border: 1px solid rgba(124,58,237,.3); }
.btn-edit:hover { background: rgba(124,58,237,.28); }
.btn-toggle { background: rgba(100,116,139,.12); color: #94A3B8; border: 1px solid rgba(100,116,139,.25); }
.btn-toggle:hover { background: rgba(100,116,139,.25); }
.btn-del  { background: rgba(239,68,68,.1);  color: #FCA5A5; border: 1px solid rgba(239,68,68,.25); }
.btn-del:hover { background: rgba(239,68,68,.22); }

.empty-row td { text-align: center; padding: 2.5rem; color: #4B6A9B; }

/* ── Formulario ─────────────────────────────────────────── */
.com-form-card {
  background: rgba(8,15,39,.85);
  border: 1px solid rgba(124,58,237,.2);
  border-radius: 16px; padding: 1.5rem;
  position: sticky; top: 1.25rem;
}
.com-form-title {
  font-family: 'Raleway', sans-serif;
  font-size: .95rem; font-weight: 800;
  color: #EFF6FF; margin-bottom: 1.25rem;
  padding-bottom: .75rem;
  border-bottom: 1px solid rgba(124,58,237,.15);
}

.form-group { margin-bottom: 1rem; }
.form-label {
  display: block; font-size: .75rem; font-weight: 700;
  color: #7B8BB5; margin-bottom: .35rem;
  text-transform: uppercase; letter-spacing: .06em;
}
.form-control {
  width: 100%; box-sizing: border-box;
  background: rgba(5,10,28,.8);
  border: 1.5px solid rgba(124,58,237,.2);
  border-radius: 8px; color: #EFF6FF;
  padding: .55rem .75rem; font-size: .87rem;
  transition: border-color .15s;
  font-family: 'DM Sans', sans-serif;
}
.form-control:focus {
  outline: none;
  border-color: rgba(124,58,237,.6);
  box-shadow: 0 0 0 3px rgba(124,58,237,.1);
}
textarea.form-control { resize: vertical; min-height: 90px; }
select.form-control { cursor: pointer; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }

.form-check {
  display: flex; align-items: center; gap: .55rem;
  font-size: .84rem; color: #94A3B8; cursor: pointer;
}
.form-check input[type="checkbox"] { cursor: pointer; accent-color: #7C3AED; }

.form-checks { display: flex; gap: 1.5rem; flex-wrap: wrap; }

.btn-primary-form {
  width: 100%; padding: .65rem 1rem;
  background: linear-gradient(135deg, #7C3AED, #9333EA);
  border: none; border-radius: 9px; color: #fff;
  font-family: 'Raleway', sans-serif;
  font-size: .88rem; font-weight: 800;
  cursor: pointer; transition: all .18s;
  box-shadow: 0 3px 14px rgba(124,58,237,.35);
  margin-top: .25rem;
}
.btn-primary-form:hover {
  background: linear-gradient(135deg, #6D28D9, #7C3AED);
  box-shadow: 0 5px 20px rgba(124,58,237,.5);
}
.btn-cancel {
  display: block; width: 100%; padding: .55rem 1rem;
  background: transparent; border: 1.5px solid rgba(255,255,255,.1);
  border-radius: 9px; color: #7B8BB5;
  font-family: 'Raleway', sans-serif; font-size: .84rem; font-weight: 700;
  cursor: pointer; transition: all .18s; text-align: center;
  text-decoration: none; margin-top: .5rem;
}
.btn-cancel:hover { border-color: rgba(255,255,255,.22); color: #CBD5E1; }

.hint { font-size: .73rem; color: #4B6A9B; margin-top: .25rem; }
</style>
</head>
<body>

<?php
$stats = getStats();
include __DIR__ . '/partials/topbar.php';
include __DIR__ . '/partials/sidebar.php';
?>

<main class="admin-main">
  <div class="admin-header">
    <div>
      <h1 class="admin-title">💳 Cuotas y Tarifas</h1>
      <p class="admin-subtitle">Gestioná los planes y precios que ven los asociados en el sitio público.</p>
    </div>
    <a href="../comisiones.php" target="_blank" class="btn-secondary" style="text-decoration:none;padding:.5rem 1rem;border-radius:9px;font-size:.82rem;font-weight:700;background:rgba(124,58,237,.12);border:1.5px solid rgba(124,58,237,.3);color:#A78BFA;">
      🌐 Ver página
    </a>
  </div>

  <?php if ($success): ?><div class="flash-success"><?= $success ?></div><?php endif ?>
  <?php if ($error):   ?><div class="flash-danger"><?= $error ?></div><?php endif ?>

  <div class="com-layout">

    <!-- ── TABLA ─────────────────────────────────────────── -->
    <div>
      <div class="com-table-wrap">
        <table class="com-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Nombre / Descripción</th>
              <th>Precio</th>
              <th>Período</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($planes)): ?>
            <tr class="empty-row">
              <td colspan="6">No hay cuotas cargadas todavía. Creá la primera desde el formulario.</td>
            </tr>
            <?php else: foreach ($planes as $p): ?>
            <tr>
              <td><?= $p['id'] ?></td>
              <td>
                <div class="plan-name">
                  <?= htmlspecialchars($p['nombre']) ?>
                  <?php if ($p['destacado']): ?>
                    &nbsp;<span class="badge-dest">⭐ Recomendado</span>
                  <?php endif ?>
                </div>
                <?php if (!empty($p['descripcion'])): ?>
                  <div style="font-size:.76rem;color:#4B6A9B;margin-top:.18rem">
                    <?= htmlspecialchars(mb_substr($p['descripcion'], 0, 60)) ?><?= mb_strlen($p['descripcion']) > 60 ? '…' : '' ?>
                  </div>
                <?php endif ?>
              </td>
              <td class="plan-price">$&nbsp;<?= number_format($p['precio'], 2, ',', '.') ?></td>
              <td><span class="badge-periodo"><?= $periodoLabel[$p['periodo']] ?? $p['periodo'] ?></span></td>
              <td>
                <span class="badge-activo <?= $p['activo'] ? 'badge-on' : 'badge-off' ?>">
                  <?= $p['activo'] ? 'Activo' : 'Oculto' ?>
                </span>
              </td>
              <td>
                <div class="com-actions">
                  <a href="?edit=<?= $p['id'] ?>" class="btn-edit">✏️ Editar</a>
                  <a href="?toggle=<?= $p['id'] ?>&csrf=<?= $csrf ?>"
                     class="btn-toggle"
                     title="<?= $p['activo'] ? 'Ocultar' : 'Mostrar' ?>">
                    <?= $p['activo'] ? '👁️ Ocultar' : '👁️ Mostrar' ?>
                  </a>
                  <a href="?delete=<?= $p['id'] ?>&csrf=<?= $csrf ?>"
                     class="btn-del"
                     onclick="return confirm('¿Eliminar esta cuota? Esta acción no se puede deshacer.')">
                    🗑️
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; endif ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── FORMULARIO ───────────────────────────────────── -->
    <div class="com-form-card">
      <div class="com-form-title">
        <?= $edit ? '✏️ Editando: ' . htmlspecialchars($edit['nombre']) : '➕ Nueva Cuota' ?>
      </div>

      <form method="POST" action="comisiones.php<?= $edit ? '?edit='.$edit['id'] : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="_action" value="<?= $edit ? 'edit' : 'add' ?>">
        <?php if ($edit): ?>
          <input type="hidden" name="id" value="<?= $edit['id'] ?>">
        <?php endif ?>

        <div class="form-group">
          <label class="form-label" for="nombre">Nombre del plan *</label>
          <input type="text" id="nombre" name="nombre" class="form-control"
                 value="<?= htmlspecialchars($edit['nombre'] ?? '') ?>"
                 placeholder="Ej: Cuota Familiar" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="descripcion">Descripción</label>
          <textarea id="descripcion" name="descripcion" class="form-control"
                    placeholder="Breve descripción visible en el sitio…"><?= htmlspecialchars($edit['descripcion'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="precio">Precio ($) *</label>
            <input type="number" id="precio" name="precio" class="form-control"
                   value="<?= $edit ? number_format((float)$edit['precio'], 2, '.', '') : '' ?>"
                   min="0" step="0.01" placeholder="0.00" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="periodo">Período *</label>
            <select id="periodo" name="periodo" class="form-control">
              <?php foreach ($periodoLabel as $val => $label): ?>
              <option value="<?= $val ?>" <?= ($edit['periodo'] ?? 'mensual') === $val ? 'selected' : '' ?>>
                <?= $label ?>
              </option>
              <?php endforeach ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="caracteristicas">Características incluidas</label>
          <textarea id="caracteristicas" name="caracteristicas" class="form-control"
                    style="min-height:110px"
                    placeholder="Una característica por línea:&#10;Acceso a campañas&#10;Solicitud de uniformes&#10;Soporte prioritario"><?= htmlspecialchars($edit['caracteristicas'] ?? '') ?></textarea>
          <div class="hint">Una característica por línea. Se muestran con ✓ en la página pública.</div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="sort_order">Orden</label>
            <input type="number" id="sort_order" name="sort_order" class="form-control"
                   value="<?= (int)($edit['sort_order'] ?? 0) ?>"
                   min="0" step="1" placeholder="0">
          </div>
        </div>

        <div class="form-checks" style="margin-bottom:1.1rem">
          <label class="form-check">
            <input type="checkbox" name="activo" value="1"
                   <?= ($edit ? $edit['activo'] : 1) ? 'checked' : '' ?>>
            Visible en el sitio
          </label>
          <label class="form-check">
            <input type="checkbox" name="destacado" value="1"
                   <?= !empty($edit['destacado']) ? 'checked' : '' ?>>
            ⭐ Marcar como recomendado
          </label>
        </div>

        <button type="submit" class="btn-primary-form">
          <?= $edit ? '💾 Guardar cambios' : '➕ Crear cuota' ?>
        </button>

        <?php if ($edit): ?>
          <a href="comisiones.php" class="btn-cancel">Cancelar</a>
        <?php endif ?>
      </form>
    </div>

  </div><!-- /com-layout -->
</main>

<script src="../assets/app.js"></script>
</body>
</html>
