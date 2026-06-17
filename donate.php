<?php
ob_start();
require_once __DIR__ . '/functions.php';
$user = requireLogin();

/* ─── Campañas: padres e hijos ─── */
$allCampaigns  = dbFetchAll('SELECT * FROM campaigns WHERE status="active" ORDER BY parent_id IS NOT NULL, parent_id, name');
$campaigns     = array_filter($allCampaigns, fn($c) => !$c['parent_id']);
$subCampaigns  = [];
foreach ($allCampaigns as $c) { if ($c['parent_id']) $subCampaigns[$c['parent_id']][] = $c; }
$preselected   = (int)($_GET['campaign'] ?? 0);
$donAmounts    = explode(',', getSetting('donation_amounts', '500,1000,2000'));

$error = ''; $mpUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $error = 'Token inválido.'; }
    else {
        $amount     = (float)str_replace(',', '.', $_POST['amount'] ?? 0);
        $campaignId = (int)($_POST['campaign_id'] ?? 0) ?: null;
        $method     = $_POST['method'] ?? 'mercadopago';

        if ($amount < 100) { $error = 'El monto mínimo es $100.'; }
        else {
            $donationId = createDonationRecord($user['id'], $amount, $campaignId, $method);

            if ($method === 'mercadopago') {
                $campaignName = $campaignId
                    ? (dbFetch('SELECT name FROM campaigns WHERE id=?', [$campaignId])['name'] ?? 'General')
                    : 'Fondo General';

                $items = [[
                    'title'       => "Donación — Cooperativa FLB — $campaignName",
                    'quantity'    => 1,
                    'unit_price'  => (float)$amount,
                    'currency_id' => 'ARS',
                ]];

                $mp = mpCreatePreference($items, $amount, $user['id'], $donationId);

                if ($mp['ok']) {
                    dbUpdate('donations', ['mp_preference_id' => $mp['preference_id']], 'id=?', [$donationId]);
                    header('Location: ' . $mp['init_point']);
                    exit;
                } else {
                    $error = 'Error al conectar con MercadoPago: ' . ($mp['error'] ?? 'Intente más tarde');
                    dbQuery('DELETE FROM donations WHERE id=?', [$donationId]);
                }
            } else {
                // Card via MP Checkout Pro (same flow, just labeled differently)
                $items = [[
                    'title'       => 'Donación con Tarjeta — Cooperativa FLB',
                    'quantity'    => 1,
                    'unit_price'  => (float)$amount,
                    'currency_id' => 'ARS',
                ]];
                $mp = mpCreatePreference($items, $amount, $user['id'], $donationId);
                if ($mp['ok']) {
                    dbUpdate('donations', ['mp_preference_id' => $mp['preference_id']], 'id=?', [$donationId]);
                    header('Location: ' . $mp['init_point']);
                    exit;
                } else {
                    $error = 'Error al iniciar pago: ' . ($mp['error'] ?? '');
                    dbQuery('DELETE FROM donations WHERE id=?', [$donationId]);
                }
            }
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
<title>Donar — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Raleway:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
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
      <div>
        <h1 class="page-title">Realizar Donación</h1>
        <p class="page-subtitle">Tu aporte hace la diferencia para nuestra institución.</p>
      </div>
    </div>

    <?php
ob_start(); if ($error): ?>
      <div class="alert alert-error animate-up"><span>⚠</span><?= h($error) ?></div>
    <?php
ob_start(); endif ?>

    <form method="post" class="donate-form animate-up" style="animation-delay:.1s" id="donateForm">
      <?= csrfField() ?>

      <!-- Amount selection -->
      <div class="section-card">
        <h3 class="donate-section-title">💰 Elegí el monto</h3>
        <div class="amount-grid">
          <?php
ob_start(); foreach ($donAmounts as $a): $a = (int)trim($a); ?>
          <button type="button" class="amount-btn" data-amount="<?= $a ?>"><?= money($a) ?></button>
          <?php
ob_start(); endforeach ?>
          <button type="button" class="amount-btn amount-custom-btn" data-amount="custom">Otro monto</button>
        </div>
        <div class="custom-amount-wrap" id="customAmountWrap" style="display:none;margin-top:1rem">
          <label class="form-label">Monto personalizado (mínimo $100)</label>
          <div class="amount-input-wrap">
            <span class="amount-prefix">$</span>
            <input type="number" id="customAmountInput" class="form-input" min="100" step="50" placeholder="Ingresá el monto">
          </div>
        </div>
        <input type="hidden" name="amount" id="amountInput" value="">
        <div class="amount-preview" id="amountPreview" style="display:none">
          Vas a donar: <strong id="amountPreviewVal" class="gradient-text"></strong>
        </div>
      </div>

      <!-- Campaign selection -->
      <?php if (!empty($campaigns) || !empty($subCampaigns)): ?>
      <div class="section-card" style="margin-top:1.25rem">
        <h3 class="donate-section-title">🎯 Destino de tu donación</h3>
        <style>
          .camp-opt-img{width:100%;height:80px;object-fit:cover;border-radius:8px;margin-bottom:.5rem;display:block}
          .camp-opt-card{position:relative}
          .sub-camps-group{grid-column:1/-1;margin-top:.5rem}
          .sub-camps-label{font-size:.78rem;color:var(--text-muted,#7B6A9B);margin-bottom:.5rem;display:block}
          .sub-camps-inner{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.6rem}
        </style>
        <div class="campaign-select-grid">

          <!-- Fondo general -->
          <label class="camp-option">
            <input type="radio" name="campaign_id" value="" <?= !$preselected ? 'checked' : '' ?>>
            <div class="camp-opt-card">
              <div class="camp-opt-emoji">🤝</div>
              <div class="camp-opt-name">Fondo General</div>
              <div class="camp-opt-desc">La cooperativa distribuye donde más se necesite</div>
            </div>
          </label>

          <!-- Campañas principales -->
          <?php foreach ($campaigns as $c): ?>
          <label class="camp-option">
            <input type="radio" name="campaign_id" value="<?= $c['id'] ?>" <?= $preselected == $c['id'] ? 'checked' : '' ?>>
            <div class="camp-opt-card">
              <?php if ($c['image']): ?>
                <img src="<?= h($c['image']) ?>" alt="" class="camp-opt-img">
              <?php else: ?>
                <div class="camp-opt-emoji"><?= h($c['emoji'] ?? '🎯') ?></div>
              <?php endif ?>
              <div class="camp-opt-name"><?= h($c['name']) ?></div>
              <div class="camp-opt-desc"><?= h(substr($c['description'], 0, 70)) ?>...</div>
            </div>
          </label>
          <?php endforeach ?>

          <!-- Sub-campañas agrupadas -->
          <?php foreach ($campaigns as $c): $mySubs = $subCampaigns[$c['id']] ?? []; if (empty($mySubs)) continue; ?>
          <div class="sub-camps-group">
            <span class="sub-camps-label">Sub-campañas de <?= h($c['name']) ?></span>
            <div class="sub-camps-inner">
              <?php foreach ($mySubs as $s): ?>
              <label class="camp-option">
                <input type="radio" name="campaign_id" value="<?= $s['id'] ?>" <?= $preselected == $s['id'] ? 'checked' : '' ?>>
                <div class="camp-opt-card">
                  <?php if ($s['image']): ?>
                    <img src="<?= h($s['image']) ?>" alt="" class="camp-opt-img">
                  <?php else: ?>
                    <div class="camp-opt-emoji"><?= h($s['emoji'] ?? '🎯') ?></div>
                  <?php endif ?>
                  <div class="camp-opt-name" style="font-size:.82rem"><?= h($s['name']) ?></div>
                </div>
              </label>
              <?php endforeach ?>
            </div>
          </div>
          <?php endforeach ?>

        </div>
      </div>
      <?php endif ?>

      <!-- Payment method -->
      <div class="section-card" style="margin-top:1.25rem">
        <h3 class="donate-section-title">💳 Método de pago</h3>
        <div class="method-grid">
          <label class="method-option">
            <input type="radio" name="method" value="mercadopago" checked>
            <div class="method-card">
              <div class="method-icon">🔵</div>
              <div class="method-name">MercadoPago</div>
              <div class="method-desc">Tarjetas, transferencia, efectivo y más</div>
            </div>
          </label>
          <label class="method-option">
            <input type="radio" name="method" value="card">
            <div class="method-card">
              <div class="method-icon">💳</div>
              <div class="method-name">Tarjeta de Crédito/Débito</div>
              <div class="method-desc">MasterCard, Visa y otras</div>
            </div>
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-hero btn-full" id="submitBtn" disabled style="margin-top:1.5rem">
        <span class="btn-text">Seleccioná un monto para continuar</span>
      </button>
      <p class="donate-note">Serás redirigido a MercadoPago para completar el pago de forma segura 🔒</p>
    </form>
  </div>
</main>

<script src="assets/app.js"></script>
<script>
// Donation form logic
const amountBtns = document.querySelectorAll('.amount-btn');
const amountInput = document.getElementById('amountInput');
const customWrap  = document.getElementById('customAmountWrap');
const customInput = document.getElementById('customAmountInput');
const preview     = document.getElementById('amountPreview');
const previewVal  = document.getElementById('amountPreviewVal');
const submitBtn   = document.getElementById('submitBtn');
const campOptions = document.querySelectorAll('.camp-option input');
const methodOptions = document.querySelectorAll('.method-option input');

function formatMoney(n) {
  return '$' + Number(n).toLocaleString('es-AR', {minimumFractionDigits:0});
}

function updateSubmit() {
  const amt = parseFloat(amountInput.value);
  if (amt >= 100) {
    submitBtn.disabled = false;
    submitBtn.querySelector('.btn-text').textContent = 'Confirmar Donación de ' + formatMoney(amt);
    preview.style.display = 'block';
    previewVal.textContent = formatMoney(amt);
  } else {
    submitBtn.disabled = true;
    submitBtn.querySelector('.btn-text').textContent = 'Seleccioná un monto para continuar';
    preview.style.display = 'none';
  }
}

function updateCampCards() {
  document.querySelectorAll('.camp-option').forEach(el => {
    el.classList.toggle('selected', el.querySelector('input').checked);
  });
}
function updateMethodCards() {
  document.querySelectorAll('.method-option').forEach(el => {
    el.classList.toggle('selected', el.querySelector('input').checked);
  });
}

amountBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    amountBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const v = btn.dataset.amount;
    if (v === 'custom') {
      customWrap.style.display = 'block';
      amountInput.value = customInput.value || '';
    } else {
      customWrap.style.display = 'none';
      amountInput.value = v;
    }
    updateSubmit();
  });
});

customInput.addEventListener('input', () => {
  amountInput.value = customInput.value;
  updateSubmit();
});

campOptions.forEach(r => r.addEventListener('change', updateCampCards));
methodOptions.forEach(r => r.addEventListener('change', updateMethodCards));
updateCampCards(); updateMethodCards();

// Pre-select first amount
if (amountBtns.length > 0) {
  amountBtns[0].click();
}
</script>
</body></html>
