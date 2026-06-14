<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lendro VTU — Buy Airtime, Data, Bills</title>
  <style>
    /* ── Reset & base ──────────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green:   #16a34a;
      --green-d: #15803d;
      --blue:    #2563eb;
      --red:     #dc2626;
      --amber:   #d97706;
      --gray-50: #f9fafb;
      --gray-100:#f3f4f6;
      --gray-200:#e5e7eb;
      --gray-400:#9ca3af;
      --gray-600:#4b5563;
      --gray-800:#1f2937;
      --white:   #ffffff;
      --shadow:  0 2px 8px rgba(0,0,0,0.10);
    }

    body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      background: var(--gray-50);
      color: var(--gray-800);
      min-height: 100vh;
    }

    /* ── Top nav ───────────────────────────────────────────────────────── */
    .nav {
      background: var(--green);
      color: var(--white);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 1.5rem;
      height: 56px;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: var(--shadow);
    }
    .nav-brand { font-size: 1.4rem; font-weight: 800; letter-spacing: -0.5px; }
    .nav-brand span { color: #bbf7d0; }

    .nav-right { display: flex; gap: .75rem; align-items: center; }
    .wallet-badge {
      background: rgba(255,255,255,.2);
      border-radius: 20px;
      padding: .25rem .75rem;
      font-size: .85rem;
      font-weight: 600;
    }
    .btn-logout {
      background: transparent;
      border: 1px solid rgba(255,255,255,.5);
      color: var(--white);
      border-radius: 6px;
      padding: .3rem .75rem;
      cursor: pointer;
      font-size: .85rem;
    }

    /* ── Main layout ───────────────────────────────────────────────────── */
    .container { max-width: 900px; margin: 0 auto; padding: 1.5rem 1rem; }

    /* ── Auth card ─────────────────────────────────────────────────────── */
    .auth-card {
      max-width: 420px;
      margin: 3rem auto;
      background: var(--white);
      border-radius: 12px;
      box-shadow: var(--shadow);
      padding: 2rem;
    }
    .auth-card h2 { font-size: 1.4rem; margin-bottom: 1.5rem; }
    .auth-toggle { text-align: center; margin-top: 1rem; font-size: .9rem; color: var(--gray-600); }
    .auth-toggle a { color: var(--green); cursor: pointer; font-weight: 600; }

    /* ── Form elements ─────────────────────────────────────────────────── */
    .form-group { margin-bottom: 1rem; }
    label { display: block; font-size: .875rem; font-weight: 600; margin-bottom: .35rem; color: var(--gray-600); }
    input, select {
      width: 100%;
      padding: .6rem .85rem;
      border: 1.5px solid var(--gray-200);
      border-radius: 8px;
      font-size: .95rem;
      outline: none;
      transition: border .2s;
    }
    input:focus, select:focus { border-color: var(--green); }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .4rem;
      padding: .65rem 1.25rem;
      border: none;
      border-radius: 8px;
      font-size: .95rem;
      font-weight: 600;
      cursor: pointer;
      transition: opacity .15s, transform .1s;
    }
    .btn:active { transform: scale(.97); }
    .btn:disabled { opacity: .55; cursor: not-allowed; }
    .btn-primary   { background: var(--green); color: var(--white); width: 100%; }
    .btn-primary:hover:not(:disabled) { background: var(--green-d); }
    .btn-secondary { background: var(--gray-100); color: var(--gray-800); }

    /* ── Tabs ──────────────────────────────────────────────────────────── */
    .tabs {
      display: flex;
      gap: .35rem;
      background: var(--gray-100);
      border-radius: 10px;
      padding: .35rem;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
    }
    .tab-btn {
      flex: 1;
      padding: .55rem .5rem;
      border: none;
      border-radius: 7px;
      background: transparent;
      font-weight: 600;
      font-size: .88rem;
      cursor: pointer;
      color: var(--gray-600);
      transition: background .2s, color .2s;
      white-space: nowrap;
      text-align: center;
    }
    .tab-btn.active { background: var(--white); color: var(--green); box-shadow: var(--shadow); }

    /* ── Network pills ─────────────────────────────────────────────────── */
    .network-pills { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .pill {
      padding: .35rem .85rem;
      border-radius: 20px;
      border: 1.5px solid var(--gray-200);
      background: var(--white);
      font-size: .83rem;
      font-weight: 600;
      cursor: pointer;
      text-transform: uppercase;
      transition: all .2s;
    }
    .pill.active { border-color: var(--green); background: #dcfce7; color: var(--green-d); }

    /* ── Service cards grid ────────────────────────────────────────────── */
    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: .85rem;
    }
    .service-card {
      background: var(--white);
      border: 1.5px solid var(--gray-200);
      border-radius: 10px;
      padding: 1rem;
      cursor: pointer;
      transition: border-color .2s, box-shadow .2s, transform .15s;
    }
    .service-card:hover { border-color: var(--green); box-shadow: 0 0 0 3px #dcfce7; transform: translateY(-2px); }
    .service-card.selected { border-color: var(--green); background: #f0fdf4; box-shadow: 0 0 0 3px #bbf7d0; }
    .service-card-name { font-weight: 700; font-size: .92rem; margin-bottom: .3rem; }
    .service-card-meta { font-size: .78rem; color: var(--gray-400); }
    .service-card-price { font-size: 1.1rem; font-weight: 800; color: var(--green); margin-top: .5rem; }
    .service-card-price span { font-size: .75rem; font-weight: 500; color: var(--gray-400); }

    /* ── Purchase panel ────────────────────────────────────────────────── */
    .purchase-panel {
      background: var(--white);
      border-radius: 12px;
      box-shadow: var(--shadow);
      padding: 1.5rem;
      margin-top: 1.5rem;
    }
    .purchase-panel h3 { margin-bottom: 1rem; font-size: 1.1rem; }
    .selected-service-info {
      background: #f0fdf4;
      border: 1.5px solid #bbf7d0;
      border-radius: 8px;
      padding: .85rem 1rem;
      margin-bottom: 1.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    /* ── Status banner ─────────────────────────────────────────────────── */
    .status-banner {
      border-radius: 8px;
      padding: 1rem 1.25rem;
      margin-top: 1rem;
      font-size: .9rem;
      display: none;
    }
    .status-banner.show { display: block; }
    .status-banner.success { background: #dcfce7; color: #166534; border-left: 4px solid var(--green); }
    .status-banner.failed  { background: #fee2e2; color: #991b1b; border-left: 4px solid var(--red); }
    .status-banner.pending { background: #fef3c7; color: #92400e; border-left: 4px solid var(--amber); }
    .status-banner.info    { background: #dbeafe; color: #1e40af; border-left: 4px solid var(--blue); }
    .status-banner strong  { display: block; font-size: 1rem; margin-bottom: .25rem; }

    /* ── Transaction list ──────────────────────────────────────────────── */
    .tx-list { display: flex; flex-direction: column; gap: .6rem; }
    .tx-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 8px;
      padding: .85rem 1rem;
    }
    .tx-left strong { font-size: .92rem; }
    .tx-left small { display: block; color: var(--gray-400); font-size: .78rem; }
    .tx-right { text-align: right; }
    .tx-amount { font-weight: 700; font-size: .95rem; }
    .tx-status {
      font-size: .75rem;
      font-weight: 600;
      padding: .2rem .6rem;
      border-radius: 20px;
      display: inline-block;
      margin-top: .2rem;
    }
    .tx-status.success  { background: #dcfce7; color: #166534; }
    .tx-status.failed   { background: #fee2e2; color: #991b1b; }
    .tx-status.reversed { background: #fee2e2; color: #991b1b; }
    .tx-status.pending  { background: #fef3c7; color: #92400e; }
    .tx-status.processing { background: #dbeafe; color: #1e40af; }

    /* ── Empty / loading states ────────────────────────────────────────── */
    .empty-state { text-align: center; padding: 2.5rem; color: var(--gray-400); }
    .spinner {
      width: 24px; height: 24px;
      border: 3px solid var(--gray-200);
      border-top-color: var(--green);
      border-radius: 50%;
      animation: spin .7s linear infinite;
      display: inline-block;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .loading-overlay {
      text-align: center; padding: 2rem;
      display: flex; flex-direction: column; align-items: center; gap: .75rem;
      color: var(--gray-400);
    }

    /* ── Responsive ────────────────────────────────────────────────────── */
    @media (max-width: 600px) {
      .services-grid { grid-template-columns: repeat(2, 1fr); }
      .nav-brand { font-size: 1.1rem; }
    }
  </style>
</head>
<body>

<!-- ── Navigation ────────────────────────────────────────────────────────── -->
<nav class="nav" id="navbar" style="display:none">
  <div class="nav-brand">Lendro<span>Pay</span></div>
  <div class="nav-right">
    <span class="wallet-badge">₦<span id="walletBalance">0.00</span></span>
    <button class="btn-logout" onclick="logout()">Log out</button>
  </div>
</nav>

<!-- ── Auth Screen ────────────────────────────────────────────────────────── -->
<div id="authScreen">
  <div class="auth-card">
    <h2 id="authTitle">Login to Lendro</h2>

    <!-- Login form -->
    <form id="loginForm">
      <div class="form-group">
        <label>Email address</label>
        <input type="email" id="loginEmail" placeholder="you@example.com" required />
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="loginPassword" placeholder="••••••••" required />
      </div>
      <div id="loginError" class="status-banner failed"></div>
      <br/>
      <button type="submit" class="btn btn-primary" id="loginBtn">Log in</button>
      <p class="auth-toggle">Don't have an account? <a onclick="showRegister()">Register here</a></p>
    </form>

    <!-- Register form -->
    <form id="registerForm" style="display:none">
      <div class="form-group">
        <label>Full name</label>
        <input type="text" id="regName" placeholder="Emeka Okafor" required />
      </div>
      <div class="form-group">
        <label>Email address</label>
        <input type="email" id="regEmail" placeholder="you@example.com" required />
      </div>
      <div class="form-group">
        <label>Phone number</label>
        <input type="tel" id="regPhone" placeholder="08011111111" required />
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="regPassword" placeholder="Min 8 characters" required />
      </div>
      <div id="regError" class="status-banner failed"></div>
      <br/>
      <button type="submit" class="btn btn-primary" id="regBtn">Create account</button>
      <p class="auth-toggle">Already have an account? <a onclick="showLogin()">Log in</a></p>
    </form>
  </div>
</div>

<!-- ── Main App Screen ────────────────────────────────────────────────────── -->
<div id="appScreen" style="display:none">
  <div class="container">

    <!-- Service type tabs -->
    <div class="tabs">
      <button class="tab-btn active" onclick="switchTab('airtime', this)">📱 Airtime</button>
      <button class="tab-btn" onclick="switchTab('data', this)">📡 Data</button>
      <button class="tab-btn" onclick="switchTab('bill_electricity', this)">⚡ Electricity</button>
      <button class="tab-btn" onclick="switchTab('bill_cable', this)">📺 Cable TV</button>
      <button class="tab-btn" onclick="switchTab('bill_education', this)">🎓 Exam PIN</button>
      <button class="tab-btn" onclick="switchTab('history', this)">🧾 History</button>
    </div>

    <!-- Service listing area -->
    <div id="servicesArea">
      <!-- Network pills -->
      <div class="network-pills" id="networkPills"></div>

      <!-- Cards grid -->
      <div id="servicesGrid">
        <div class="loading-overlay"><div class="spinner"></div><span>Loading services…</span></div>
      </div>

      <!-- Purchase panel — appears after selecting a service -->
      <div class="purchase-panel" id="purchasePanel" style="display:none">
        <h3>Complete your purchase</h3>

        <div class="selected-service-info">
          <div>
            <strong id="selectedName">—</strong>
            <small id="selectedMeta">—</small>
          </div>
          <strong id="selectedPrice" style="font-size:1.2rem;color:var(--green)">—</strong>
        </div>

        <div class="form-group">
          <label>Recipient phone number</label>
          <input type="tel" id="purchasePhone" placeholder="08011111111" maxlength="14" />
        </div>

        <button class="btn btn-primary" id="purchaseBtn" onclick="submitPurchase()">
          Buy Now
        </button>

        <div id="purchaseStatus" class="status-banner"></div>
      </div>
    </div>

    <!-- Transaction history area -->
    <div id="historyArea" style="display:none">
      <div id="txList">
        <div class="loading-overlay"><div class="spinner"></div><span>Loading history…</span></div>
      </div>
    </div>

  </div>
</div>

<script>
// ── State ──────────────────────────────────────────────────────────────────────
const API = '../api/v1'; // adjust if your folder structure differs
let currentUser   = null;
let allServices   = {};         // { airtime: { mtn: [...] }, data: {...}, bill: {...} }
let selectedTab   = 'airtime';
let selectedNet   = '';
let selectedSvc   = null;       // the chosen service card object
let pollTimer     = null;

// ── Boot ───────────────────────────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
  const saved = sessionStorage.getItem('lendro_user');
  if (saved) {
    currentUser = JSON.parse(saved);
    enterApp();
  }
});

// ── Auth helpers ───────────────────────────────────────────────────────────────
function showRegister() {
  document.getElementById('loginForm').style.display   = 'none';
  document.getElementById('registerForm').style.display = '';
  document.getElementById('authTitle').textContent      = 'Create an account';
}
function showLogin() {
  document.getElementById('registerForm').style.display = 'none';
  document.getElementById('loginForm').style.display    = '';
  document.getElementById('authTitle').textContent      = 'Login to Lendro';
}

document.getElementById('loginForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('loginBtn');
  btn.disabled = true; btn.textContent = 'Logging in…';
  clearBanner('loginError');

  const res = await api('POST', '/auth/login.php', {
    email:    document.getElementById('loginEmail').value,
    password: document.getElementById('loginPassword').value,
  });

  btn.disabled = false; btn.textContent = 'Log in';

  if (res.status === 'success') {
    currentUser = res.user;
    sessionStorage.setItem('lendro_user', JSON.stringify(currentUser));
    enterApp();
  } else {
    showBanner('loginError', res.message || 'Login failed.', 'failed');
  }
});

document.getElementById('registerForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('regBtn');
  btn.disabled = true; btn.textContent = 'Creating account…';
  clearBanner('regError');

  const res = await api('POST', '/auth/register.php', {
    name:     document.getElementById('regName').value,
    email:    document.getElementById('regEmail').value,
    phone:    document.getElementById('regPhone').value,
    password: document.getElementById('regPassword').value,
  });

  btn.disabled = false; btn.textContent = 'Create account';

  if (res.status === 'success') {
    showBanner('regError', '✅ Account created! Please log in.', 'success');
    setTimeout(showLogin, 1500);
  } else {
    const msg = res.message || (res.errors || []).join(' ') || 'Registration failed.';
    showBanner('regError', msg, 'failed');
  }
});

async function logout() {
  await api('POST', '/auth/logout.php');
  sessionStorage.clear();
  currentUser = null;
  document.getElementById('appScreen').style.display  = 'none';
  document.getElementById('authScreen').style.display = '';
  document.getElementById('navbar').style.display     = 'none';
}

// ── Enter main app ─────────────────────────────────────────────────────────────
async function enterApp() {
  document.getElementById('authScreen').style.display  = 'none';
  document.getElementById('appScreen').style.display   = '';
  document.getElementById('navbar').style.display      = 'flex';
  document.getElementById('walletBalance').textContent = fmtNum(currentUser.wallet_balance || 0);

  await loadServices();
  switchTab('airtime', document.querySelector('.tab-btn.active'));
}

async function refreshWallet() {
  const res = await api('GET', '/client/wallet.php');
  if (res.status === 'success') {
    document.getElementById('walletBalance').textContent = fmtNum(res.balance);
    currentUser.wallet_balance = res.balance;
    sessionStorage.setItem('lendro_user', JSON.stringify(currentUser));
  }
}

// ── Load & display services ────────────────────────────────────────────────────
async function loadServices() {
  const res = await api('GET', '/client/services.php');
  if (res.status === 'success') {
    allServices = res.data;
  } else {
    document.getElementById('servicesGrid').innerHTML =
      '<div class="empty-state">⚠️ Could not load services. Please refresh.</div>';
  }
}

function switchTab(tab, btn) {
  selectedTab = tab;
  selectedSvc = null;
  selectedNet = '';
  hidePurchasePanel();

  // Update tab buttons
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  if (tab === 'history') {
    document.getElementById('servicesArea').style.display = 'none';
    document.getElementById('historyArea').style.display  = '';
    loadHistory();
  } else {
    document.getElementById('servicesArea').style.display = '';
    document.getElementById('historyArea').style.display  = 'none';
    renderServicesForTab(tab);
  }
}

function renderServicesForTab(tab) {
  const grid  = document.getElementById('servicesGrid');
  const pills = document.getElementById('networkPills');

  // Figure out which data to use
  let pool = {};
  if (tab === 'airtime') {
    pool = allServices.airtime || {};
  } else if (tab === 'data') {
    pool = allServices.data || {};
  } else if (tab.startsWith('bill_')) {
    const cat = tab.replace('bill_', ''); // "electricity", "cable", "education"
    const billData = allServices.bill || {};
    // Filter bill entries by category
    Object.entries(billData).forEach(([net, items]) => {
      const filtered = items.filter(s => s.category === cat);
      if (filtered.length > 0) pool[net] = filtered;
    });
  }

  const networks = Object.keys(pool);

  if (networks.length === 0) {
    pills.innerHTML = '';
    grid.innerHTML  = '<div class="empty-state">No services available right now. Please sync the catalogue.</div>';
    return;
  }

  // Render network pills
  if (!selectedNet) selectedNet = networks[0];
  pills.innerHTML = networks.map(n =>
    `<span class="pill ${n === selectedNet ? 'active' : ''}" onclick="selectNetwork('${n}', this)">${n.toUpperCase()}</span>`
  ).join('');

  renderCards(pool[selectedNet] || []);
}

function selectNetwork(net, el) {
  selectedNet = net;
  selectedSvc = null;
  hidePurchasePanel();
  document.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
  el.classList.add('active');

  // Re-render from same pool
  renderServicesForTab(selectedTab);
}

function renderCards(items) {
  const grid = document.getElementById('servicesGrid');
  if (!items || items.length === 0) {
    grid.innerHTML = '<div class="empty-state">No plans available for this network.</div>';
    return;
  }

  grid.innerHTML = `<div class="services-grid">${items.map(svc => `
    <div class="service-card" id="card-${svc.id}" onclick="selectService(${JSON.stringify(JSON.stringify(svc))})">
      <div class="service-card-name">${esc(svc.name)}</div>
      <div class="service-card-meta">${svc.duration ? svc.duration + ' ' + (svc.unit || 'day') + (svc.duration > 1 ? 's' : '') : svc.category || ''}</div>
      <div class="service-card-price">
        ${svc.price ? '₦' + fmtNum(svc.price) : '<span>Flexible amount</span>'}
      </div>
    </div>
  `).join('')}</div>`;
}

function selectService(jsonStr) {
  const svc = JSON.parse(jsonStr);
  selectedSvc = svc;

  // Highlight selected card
  document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
  const el = document.getElementById('card-' + svc.id);
  if (el) el.classList.add('selected');

  // Show purchase panel
  document.getElementById('selectedName').textContent  = svc.name;
  document.getElementById('selectedMeta').textContent  = svc.duration
    ? `Valid for ${svc.duration} ${svc.unit}${svc.duration > 1 ? 's' : ''}`
    : svc.category || '';
  document.getElementById('selectedPrice').textContent = svc.price
    ? '₦' + fmtNum(svc.price)
    : 'Flexible';
  document.getElementById('purchasePanel').style.display = '';
  document.getElementById('purchaseStatus').className    = 'status-banner';
  document.getElementById('purchaseStatus').innerHTML    = '';
  document.getElementById('purchasePhone').value         = '';

  // Scroll to panel
  setTimeout(() => document.getElementById('purchasePanel').scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
}

function hidePurchasePanel() {
  document.getElementById('purchasePanel').style.display = 'none';
}

// ── Buy flow ───────────────────────────────────────────────────────────────────
async function submitPurchase() {
  if (!selectedSvc) return;

  const phone = document.getElementById('purchasePhone').value.trim();
  if (!phone) {
    showBanner('purchaseStatus', 'Please enter a phone number.', 'failed');
    return;
  }

  const btn = document.getElementById('purchaseBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Processing…';
  clearBanner('purchaseStatus');

  const idempotencyKey = crypto.randomUUID(); // unique per purchase attempt

  const res = await api('POST', '/client/order.php', {
    service_id:      selectedSvc.id,
    phone:           phone,
    idempotency_key: idempotencyKey,
  });

  btn.disabled = false; btn.textContent = 'Buy Now';

  if (res.status === 'processing' || res.status === 'already_processed') {
    showBanner('purchaseStatus',
      `<strong>✅ Order received!</strong>
       Reference: <code>${res.reference}</code><br/>
       Your service is being processed. We'll update you shortly.`,
      'pending'
    );
    // Start polling for status
    startPolling(res.reference);
    refreshWallet();
  } else {
    showBanner('purchaseStatus', `<strong>❌ Failed</strong> ${res.message || 'Something went wrong.'}`, 'failed');
  }
}

function startPolling(reference) {
  if (pollTimer) clearInterval(pollTimer);
  let attempts = 0;

  pollTimer = setInterval(async () => {
    attempts++;
    const res = await api('GET', `/client/status.php?ref=${encodeURIComponent(reference)}`);

    if (res.status === 'success') {
      const txStatus = res.tx_status;

      if (txStatus === 'success') {
        clearInterval(pollTimer);
        showBanner('purchaseStatus',
          `<strong>🎉 Successful!</strong> ${res.service} delivered to ${res.phone}.<br/>
           Reference: <code>${reference}</code>`,
          'success'
        );
        refreshWallet();
      } else if (txStatus === 'reversed' || txStatus === 'failed') {
        clearInterval(pollTimer);
        showBanner('purchaseStatus',
          `<strong>❌ Transaction failed.</strong> Your wallet has been refunded.<br/>
           Reference: <code>${reference}</code>`,
          'failed'
        );
        refreshWallet();
      }
      // If still pending/processing, keep polling
    }

    if (attempts >= 24) { // stop after ~2 minutes
      clearInterval(pollTimer);
      showBanner('purchaseStatus',
        `<strong>⏳ Still processing</strong><br/>Your transaction (<code>${reference}</code>) is taking longer than expected.
         Check your history tab for updates.`,
        'info'
      );
    }
  }, 5000); // poll every 5 seconds
}

// ── Transaction history ────────────────────────────────────────────────────────
async function loadHistory() {
  const list = document.getElementById('txList');
  list.innerHTML = '<div class="loading-overlay"><div class="spinner"></div><span>Loading history…</span></div>';

  const res = await api('GET', '/client/transactions.php?limit=30');

  if (res.status !== 'success' || !res.transactions.length) {
    list.innerHTML = '<div class="empty-state">No transactions yet.</div>';
    return;
  }

  list.innerHTML = `<div class="tx-list">${res.transactions.map(tx => `
    <div class="tx-item">
      <div class="tx-left">
        <strong>${esc(tx.service || 'VTU Purchase')}</strong>
        <small>${esc(tx.phone || '')} · ${tx.time_ago}</small>
        <small>Ref: ${esc(tx.reference)}</small>
      </div>
      <div class="tx-right">
        <div class="tx-amount">₦${fmtNum(tx.amount)}</div>
        <span class="tx-status ${tx.status}">${tx.status}</span>
      </div>
    </div>
  `).join('')}</div>`;
}

// ── Utilities ──────────────────────────────────────────────────────────────────
async function api(method, path, body) {
  try {
    const opts = {
      method,
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
    };
    if (body && method !== 'GET') {
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(API + path, opts);
    return await res.json();
  } catch (err) {
    console.error('[API error]', path, err);
    return { status: 'failed', message: 'Network error. Please check your connection.' };
  }
}

function showBanner(id, html, type) {
  const el = document.getElementById(id);
  el.innerHTML   = html;
  el.className   = `status-banner ${type} show`;
}
function clearBanner(id) {
  const el = document.getElementById(id);
  el.innerHTML = '';
  el.className = 'status-banner';
}

function fmtNum(n) {
  return Number(n).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function esc(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}
</script>
</body>
</html>
