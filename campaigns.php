<?php
require_once __DIR__ . '/../functions.php';
$admin = requireAdmin();
$stats = getStats();

$error = ''; $success = '';

/* ─── helper: guardar imagen subida ─── */
function saveCampaignImage(string $inputName): ?string {
    if (empty($_FILES[$inputName]['name'])) return null;
    $f = $_FILES[$inputName];
    if ($f['error'] !== UPLOAD_ERR_OK) return null;

    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    $mime    = mime_content_type($f['tmp_name']);
    if (!in_array($mime, $allowed, true)) return null;
    if ($f['size'] > 5 * 1024 * 1024) return null;

    $ext  = match($mime) {
        'image/jpeg' => 'jpg', 'image/png' => 'png',
        'image/webp' => 'webp', default => 'gif',
    };
    $dir  = __DIR__ . '/../uploads/campaigns/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    move_uploaded_file($f['tmp_name'], $dir . $name);
    return 'uploads/campaigns/' . $name;
}

function deleteOldImage(?string $path): void {
    if (!$path) return;
    $full = __DIR__ . '/../' . $path;
    if (is_file($full)) unlink($full);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) die('Token inválido.');
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $name     = trim($_POST['name'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $goal     = (float)str_replace(',', '.', $_POST['goal_amount'] ?? 0);
        $emoji    = trim($_POST['emoji'] ?? '🎯');
        $start    = $_POST['start_date'] ?: null;
        $end      = $_POST['end_date'] ?: null;
        $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;

        if (!$name || $goal <= 0) {
            $error = 'Nombre y meta son obligatorios.';
        } elseif ($action === 'create') {
            $img = saveCampaignImage('campaign_image');
            $id  = dbInsert('campaigns', [
                'name'        => $name, 'description' => $desc,
                'goal_amount' => $goal, 'emoji'        => $emoji,
                'image'       => $img,  'parent_id'    => $parentId,
                'start_date'  => $start,'end_date'     => $end,
                'status'      => 'active',
                'created_by'  => $admin['id'],
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
            adminLog($admin['id'], 'create_campaign', 'campaign', (int)$id, $name);
            $success = "Campaña '$name' creada.";
        } else {
            $cid     = (int)$_POST['campaign_id'];
            $old     = dbFetch('SELECT image FROM campaigns WHERE id=?', [$cid]);
            $imgPath = $old['image'] ?? null;
            $newImg  = saveCampaignImage('campaign_image');
            if ($newImg) { deleteOldImage($imgPath); $imgPath = $newImg; }
            if (!empty($_POST['remove_image']) && !$newImg) { deleteOldImage($imgPath); $imgPath = null; }
            dbUpdate('campaigns', [
                'name'        => $name,   'description' => $desc,
                'goal_amount' => $goal,   'emoji'        => $emoji,
                'image'       => $imgPath,'parent_id'    => $parentId,
                'start_date'  => $start,  'end_date'     => $end,
            ], 'id=?', [$cid]);
            adminLog($admin['id'], 'edit_campaign', 'campaign', $cid, $name);
            $success = "Campaña actualizada.";
        }
    }

    if ($action === 'status') {
        $cid    = (int)$_POST['campaign_id'];
        $status = $_POST['new_status'] ?? 'paused';
        dbUpdate('campaigns', ['status' => $status], 'id=?', [$cid]);
        adminLog($admin['id'], "set_campaign_$status", 'campaign', $cid);
        $success = 'Estado actualizado.';
    }
}

$allCampaigns = dbFetchAll(
    'SELECT c.*, COUNT(d.id) AS don_count,
            COALESCE(SUM(CASE WHEN d.status="approved" THEN d.amount END), 0) AS actual_raised
     FROM campaigns c
     LEFT JOIN donations d ON d.campaign_id = c.id
     GROUP BY c.id ORDER BY c.parent_id IS NOT NULL, c.parent_id, c.created_at DESC'
);

$parents  = [];
$children = [];
foreach ($allCampaigns as $c) {
    if ($c['parent_id']) $children[$c['parent_id']][] = $c;
    else $parents[] = $c;
}

$parentOptions = dbFetchAll('SELECT id, name FROM campaigns WHERE parent_id IS NULL ORDER BY name');
$editCamp = !empty($_GET['edit']) ? dbFetch('SELECT * FROM campaigns WHERE id=?', [(int)$_GET['edit']]) : null;
$statusColors = ['active'=>'badge-success','paused'=>'badge-warning','completed'=>'badge-info'];
$statusLabels = ['active'=>'Activa','paused'=>'Pausada','completed'=>'Completada'];
?><!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#07050F">
<title>Campañas — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/admin.css">
<style>
.camp-tree{display:flex;flex-direction:column;gap:1rem}
.camp-parent-block{background:var(--card-bg,#12101f);border:1px solid var(--border,#2a2540);border-radius:14px;overflow:hidden}
.camp-parent-header{display:flex;align-items:center;gap:.75rem;padding:1rem 1.25rem;cursor:pointer;user-select:none}
.camp-parent-header:hover{background:rgba(255,255,255,.03)}
.camp-parent-expand{font-size:.85rem;color:var(--text-muted,#7B6A9B);margin-left:auto;transition:transform .2s}
.camp-parent-block.open .camp-parent-expand{transform:rotate(180deg)}
.camp-card-main{padding:.25rem 1.25rem 1.25rem}
.camp-sub-list{border-top:1px solid var(--border,#2a2540);padding:.75rem 1.25rem;display:none;flex-direction:column;gap:.75rem}
.camp-parent-block.open .camp-sub-list{display:flex}
.camp-sub-item{display:flex;align-items:center;gap:.75rem;background:rgba(255,255,255,.03);border-radius:10px;padding:.65rem .9rem}
.camp-sub-img{width:44px;height:44px;border-radius:8px;object-fit:cover;flex-shrink:0}
.camp-sub-img-ph{width:44px;height:44px;border-radius:8px;background:rgba(255,255,255,.07);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
.camp-sub-info{flex:1;min-width:0}
.camp-sub-name{font-weight:600;font-size:.9rem}
.camp-sub-raised{font-size:.78rem;color:var(--text-muted,#7B6A9B)}
.camp-sub-actions{display:flex;gap:.4rem;flex-shrink:0;align-items:center}
.add-sub-btn{width:100%;text-align:center;padding:.5rem;border:1px dashed var(--border,#2a2540);border-radius:8px;color:var(--text-muted,#7B6A9B);cursor:pointer;font-size:.82rem;background:none;transition:all .2s}
.add-sub-btn:hover{border-color:var(--primary,#7C3AED);color:var(--primary,#7C3AED);background:rgba(124,58,237,.05)}
.img-preview-box{width:100%;height:140px;border:2px dashed var(--border,#2a2540);border-radius:10px;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:.5rem;cursor:pointer;transition:border-color .2s}
.img-preview-box:hover{border-color:var(--primary,#7C3AED)}
.img-placeholder{text-align:center;color:var(--text-muted,#7B6A9B);font-size:.82rem}
.img-placeholder span{font-size:1.8rem;display:block;margin-bottom:.3rem}
.camp-admin-img{width:100%;height:70px;object-fit:cover;border-radius:8px;margin-bottom:.5rem}
</style>
</head>
<body class="dark admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="admin-content">
    <div class="admin-page-header">
      <div><h1 class="admin-page-title">Campañas</h1><p class="admin-page-sub">Gestión de campañas y sub-campañas</p></div>
      <button class="btn btn-primary-sm" onclick="openCreate(null)">+ Nueva Campaña</button>
    </div>

    <?php if ($error): ?><div class="alert alert-error animate-up"><?= h($error) ?></div><?php endif ?>
    <?php if ($success): ?><div class="alert alert-success animate-up"><?= h($success) ?></div><?php endif ?>

    <div class="camp-tree animate-up">
      <?php foreach ($parents as $c):
        $cpct = $c['goal_amount']>0 ? min(100,round(($c['actual_raised']/$c['goal_amount'])*100,1)) : 0;
        $subs = $children[$c['id']] ?? [];
      ?>
      <div class="camp-parent-block <?= !empty($subs)?'open':'' ?>" id="block-<?= $c['id'] ?>">

        <div class="camp-parent-header" onclick="toggleBlock(<?= $c['id'] ?>)">
          <?php if ($c['image']): ?>
            <img src="../<?= h($c['image']) ?>" alt="" style="width:42px;height:42px;border-radius:8px;object-fit:cover;flex-shrink:0">
          <?php else: ?>
            <span style="font-size:1.6rem"><?= h($c['emoji']) ?></span>
          <?php endif ?>
          <div style="flex:1;min-width:0">
            <strong><?= h($c['name']) ?></strong><br>
            <span class="badge <?= $statusColors[$c['status']] ?>" style="font-size:.7rem"><?= $statusLabels[$c['status']] ?></span>
            <?php if (!empty($subs)): ?>
              <span style="font-size:.75rem;color:var(--text-muted);margin-left:.5rem"><?= count($subs) ?> sub-campaña<?= count($subs)>1?'s':'' ?></span>
            <?php endif ?>
          </div>
          <?php if (!empty($subs)): ?><span class="camp-parent-expand">▾</span><?php endif ?>
        </div>

        <div class="camp-card-main">
          <?php if ($c['image']): ?>
            <img src="../<?= h($c['image']) ?>" alt="" class="camp-admin-img">
          <?php endif ?>
          <p class="camp-admin-desc" style="margin:.25rem 0 .6rem"><?= h(substr($c['description'],0,110)) ?></p>
          <div class="cp-bar" style="margin:.5rem 0"><div class="cp-fill" style="width:<?= $cpct ?>%"></div></div>
          <div class="camp-admin-meta">
            <span><?= money((float)$c['actual_raised']) ?> de <?= money((float)$c['goal_amount']) ?></span>
            <span><?= $cpct ?>% · <?= $c['don_count'] ?> donaciones</span>
          </div>
          <div class="camp-admin-actions" style="margin-top:.75rem">
            <button class="btn btn-xs btn-outline" onclick='openEdit(<?= json_encode($c) ?>)'>✏ Editar</button>
            <?php if ($c['status']==='active'): ?>
              <form method="post" style="display:inline"><?= csrfField() ?>
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
                <input type="hidden" name="new_status" value="paused">
                <button class="btn btn-xs btn-warning" onclick="return confirm('¿Pausar?')">⏸ Pausar</button>
              </form>
            <?php elseif ($c['status']==='paused'): ?>
              <form method="post" style="display:inline"><?= csrfField() ?>
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
                <input type="hidden" name="new_status" value="active">
                <button class="btn btn-xs btn-success" onclick="return confirm('¿Activar?')">▶ Activar</button>
              </form>
            <?php endif ?>
            <form method="post" style="display:inline"><?= csrfField() ?>
              <input type="hidden" name="action" value="status">
              <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
              <input type="hidden" name="new_status" value="completed">
              <button class="btn btn-xs btn-outline" onclick="return confirm('¿Completar?')">✔ Completar</button>
            </form>
          </div>
        </div>

        <div class="camp-sub-list">
          <?php foreach ($subs as $s):
            $sp=$s['goal_amount']>0?min(100,round(($s['actual_raised']/$s['goal_amount'])*100,1)):0;
          ?>
          <div class="camp-sub-item">
            <?php if ($s['image']): ?>
              <img src="../<?= h($s['image']) ?>" alt="" class="camp-sub-img">
            <?php else: ?>
              <div class="camp-sub-img-ph"><?= h($s['emoji']) ?></div>
            <?php endif ?>
            <div class="camp-sub-info">
              <div class="camp-sub-name"><?= h($s['name']) ?></div>
              <div class="camp-sub-raised"><?= money((float)$s['actual_raised']) ?> / <?= money((float)$s['goal_amount']) ?> · <?= $sp ?>%</div>
              <div class="cp-bar" style="margin:.35rem 0 0;height:4px"><div class="cp-fill" style="width:<?= $sp ?>%"></div></div>
            </div>
            <div class="camp-sub-actions">
              <button class="btn btn-xs btn-outline" onclick='openEdit(<?= json_encode($s) ?>)'>✏</button>
              <span class="badge <?= $statusColors[$s['status']] ?>" style="font-size:.68rem"><?= $statusLabels[$s['status']] ?></span>
            </div>
          </div>
          <?php endforeach ?>
          <button class="add-sub-btn" onclick="openCreate(<?= $c['id'] ?>)">
            + Agregar sub-campaña a "<?= h($c['name']) ?>"
          </button>
        </div>

      </div>
      <?php endforeach ?>
    </div>
  </div>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="campModal" style="display:none" onclick="if(event.target===this)closeCampModal()">
  <div class="modal-box" style="max-width:560px">
    <h3 class="modal-title" id="modalTitle">+ Nueva Campaña</h3>
    <form method="post" enctype="multipart/form-data" id="campForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" id="formAction" value="create">
      <input type="hidden" name="campaign_id" id="formCampId" value="">
      <input type="hidden" name="parent_id" id="formParentId" value="">

      <div class="form-grid-2">
        <!-- Imagen -->
        <div class="form-group" style="grid-column:span 2">
          <label class="form-label">Imagen de la campaña</label>
          <div class="img-preview-box" onclick="document.getElementById('imgInput').click()" id="previewBox">
            <img id="imgPreview" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover">
            <div class="img-placeholder" id="imgPlaceholder">
              <span>🖼️</span>Clic para subir imagen<br><small>(JPG, PNG, WebP · máx 5 MB)</small>
            </div>
          </div>
          <input type="file" name="campaign_image" id="imgInput" accept="image/*" style="display:none">
          <div id="existingImgWrap" style="display:none;margin-top:.5rem">
            <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:.3rem">Imagen actual:</p>
            <img id="existingImg" src="" alt="" style="height:80px;border-radius:8px;object-fit:cover">
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;color:var(--text-muted);cursor:pointer;margin-top:.4rem">
              <input type="checkbox" name="remove_image" id="removeImg" value="1"> Eliminar imagen
            </label>
          </div>
        </div>

        <!-- Nombre -->
        <div class="form-group" style="grid-column:span 2">
          <label class="form-label">Nombre *</label>
          <input type="text" name="name" id="formName" class="form-input" required>
        </div>

        <!-- Descripción -->
        <div class="form-group" style="grid-column:span 2">
          <label class="form-label">Descripción</label>
          <textarea name="description" id="formDesc" class="form-input" rows="2"></textarea>
        </div>

        <!-- Meta y emoji -->
        <div class="form-group">
          <label class="form-label">Meta ($) *</label>
          <input type="number" name="goal_amount" id="formGoal" class="form-input" min="1" required>
        </div>
        <div class="form-group">
          <label class="form-label">Emoji</label>
          <input type="text" name="emoji" id="formEmoji" class="form-input" value="🎯" maxlength="4">
        </div>

        <!-- Fechas -->
        <div class="form-group">
          <label class="form-label">Fecha inicio</label>
          <input type="date" name="start_date" id="formStart" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">Fecha fin</label>
          <input type="date" name="end_date" id="formEnd" class="form-input">
        </div>

        <!-- Padre -->
        <div class="form-group" style="grid-column:span 2">
          <label class="form-label">Sub-campaña de</label>
          <select id="parentSelect" class="form-input">
            <option value="">— Ninguna (campaña principal) —</option>
            <?php foreach ($parentOptions as $p): ?>
            <option value="<?= $p['id'] ?>"><?= h($p['name']) ?></option>
            <?php endforeach ?>
          </select>
          <small style="color:var(--text-muted)">Opcional. Seleccioná si esta es una sub-campaña.</small>
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeCampModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="formSubmitBtn">Crear Campaña</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/app.js"></script>
<script>
function toggleBlock(id){
  const b=document.getElementById('block-'+id);
  if(b)b.classList.toggle('open');
}
function openCreate(parentId){
  resetForm();
  document.getElementById('modalTitle').textContent=parentId?'+ Nueva Sub-campaña':'+ Nueva Campaña';
  document.getElementById('formAction').value='create';
  document.getElementById('formSubmitBtn').textContent='Crear Campaña';
  if(parentId){
    document.getElementById('formParentId').value=parentId;
    document.getElementById('parentSelect').value=parentId;
  }
  document.getElementById('campModal').style.display='flex';
}
function openEdit(c){
  resetForm();
  document.getElementById('modalTitle').textContent='✏ Editar Campaña';
  document.getElementById('formAction').value='edit';
  document.getElementById('formCampId').value=c.id;
  document.getElementById('formName').value=c.name||'';
  document.getElementById('formDesc').value=c.description||'';
  document.getElementById('formGoal').value=c.goal_amount||'';
  document.getElementById('formEmoji').value=c.emoji||'🎯';
  document.getElementById('formStart').value=c.start_date||'';
  document.getElementById('formEnd').value=c.end_date||'';
  document.getElementById('formParentId').value=c.parent_id||'';
  document.getElementById('parentSelect').value=c.parent_id||'';
  document.getElementById('formSubmitBtn').textContent='Guardar Cambios';
  if(c.image){
    document.getElementById('existingImgWrap').style.display='block';
    document.getElementById('existingImg').src='../'+c.image;
  }
  document.getElementById('campModal').style.display='flex';
}
function closeCampModal(){
  document.getElementById('campModal').style.display='none';
}
function resetForm(){
  document.getElementById('campForm').reset();
  document.getElementById('formCampId').value='';
  document.getElementById('formParentId').value='';
  document.getElementById('imgPreview').style.display='none';
  document.getElementById('imgPlaceholder').style.display='block';
  document.getElementById('existingImgWrap').style.display='none';
}
document.getElementById('parentSelect').addEventListener('change',function(){
  document.getElementById('formParentId').value=this.value;
});
document.getElementById('imgInput').addEventListener('change',function(){
  const file=this.files[0]; if(!file)return;
  const reader=new FileReader();
  reader.onload=e=>{
    const img=document.getElementById('imgPreview');
    img.src=e.target.result; img.style.display='block';
    document.getElementById('imgPlaceholder').style.display='none';
  };
  reader.readAsDataURL(file);
});
<?php if ($editCamp): ?>openEdit(<?= json_encode($editCamp) ?>);<?php endif ?>
</script>
</body></html>
