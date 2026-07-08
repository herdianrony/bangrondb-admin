<?php
declare(strict_types=1);

namespace App\Inertia;

/**
 * Standalone Setup Wizard — renders a full HTML page without Vue/Inertia.
 * Used when the application has not been initialized yet.
 */
class SetupWizard
{
    private string $dbPath;

    public function __construct(string $dbPath)
    {
        $this->dbPath = $dbPath;
    }

    public function isSetupNeeded(): bool
    {
        if (!is_dir($this->dbPath)) return true;
        $client = new \BangronDB\Client($this->dbPath);
        if (!$client->dbExists('auth')) return true;
        if (!$client->collectionExists('auth', 'users')) return true;
        try {
            $u = $client->selectCollection('auth', 'users');
            return $u->count() === 0;
        } catch (\Throwable $e) {
            return true;
        }
    }

    public function render(): void
    {
        // Pre-check environment
        $checks = $this->envChecks();

        echo <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Setup — Bangron Studio</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#09090b;--surface:#111318;--elevated:#1a1d27;--border:rgba(255,255,255,.07);
  --text:#e2e8f0;--muted:#64748b;--brand:#6366f1;--brand2:#8a5cff;
  --ok:#22c55e;--warn:#f59e0b;--err:#ef4444;
  --font:'Plus Jakarta Sans',system-ui,-apple-system,sans-serif;
}
html{height:100%}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;overflow-x:hidden;-webkit-font-smoothing:antialiased}

/* Background effects */
body::before{content:'';position:fixed;inset:0;background:
  radial-gradient(ellipse 600px 400px at 20% 30%, rgba(99,102,241,.08), transparent),
  radial-gradient(ellipse 500px 350px at 80% 70%, rgba(138,92,255,.06), transparent);
  pointer-events:none;z-index:0}

.wizard{position:relative;z-index:1;width:100%;max-width:520px;padding:20px}

/* Brand */
.brand{text-align:center;margin-bottom:36px}
.brand-logo{width:56px;height:56px;margin:0 auto 16px;border-radius:18px;
  background:linear-gradient(135deg,var(--brand),var(--brand2));
  display:grid;place-items:center;
  box-shadow:0 8px 32px rgba(99,102,241,.3)}
.brand-logo svg{width:26px;height:26px;color:#fff}
.brand h1{font-size:22px;font-weight:800;color:#fff;letter-spacing:-.02em}
.brand p{font-size:13px;color:var(--muted);margin-top:4px;line-height:1.5}

/* Step indicators */
.steps{display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:32px}
.step-dot{width:32px;height:32px;border-radius:50%;display:grid;place-items:center;
  font-size:12px;font-weight:700;border:2px solid #2a2d3a;color:#4a4d5a;
  transition:all .3s ease;flex-shrink:0;position:relative}
.step-dot.active{border-color:var(--brand);background:var(--brand);color:#fff;
  box-shadow:0 0 0 4px rgba(99,102,241,.15)}
.step-dot.done{border-color:var(--ok);background:rgba(34,197,94,.12);color:var(--ok)}
.step-dot.done svg{width:14px;height:14px}
.step-line{width:40px;height:2px;border-radius:99px;background:#1e2130;transition:background .3s;margin:0 4px}
.step-line.done{background:rgba(34,197,94,.3)}

/* Card */
.card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;
  box-shadow:0 4px 24px rgba(0,0,0,.2)}
.card h2{font-size:17px;font-weight:700;color:#fff;margin-bottom:4px}
.card .subtitle{font-size:13px;color:var(--muted);margin-bottom:24px;line-height:1.5}

/* Form */
.field{margin-bottom:18px}
.field-label{display:block;font-size:11px;font-weight:600;color:var(--muted);
  margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em}
.field-input{width:100%;height:44px;background:var(--bg);border:1px solid var(--border);
  border-radius:12px;padding:0 14px;color:#fff;font-size:14px;font-family:var(--font);
  outline:none;transition:all .2s}
.field-input:focus{border-color:rgba(99,102,241,.5);box-shadow:0 0 0 3px rgba(99,102,241,.1)}
.field-input::placeholder{color:#3a3d4a}
.field-input-wrap{position:relative}
.field-input-wrap .icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);
  width:16px;height:16px;color:#4a4d5a;pointer-events:none}
.field-input-wrap .field-input{padding-left:40px}
.field-input-wrap .toggle-pass{position:absolute;right:12px;top:50%;transform:translateY(-50%);
  background:none;border:none;color:#4a4d5a;cursor:pointer;padding:4px}
.field-input-wrap .toggle-pass:hover{color:#94a3b8}

.field-hint{font-size:11px;color:#3a3d4a;margin-top:6px;padding-left:2px}
.field-error{font-size:11px;color:var(--err);margin-top:6px;padding-left:2px;display:none}
.field-error.show{display:block}

/* Buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;
  height:44px;padding:0 24px;border-radius:12px;font-size:14px;font-weight:600;
  font-family:var(--font);cursor:pointer;border:none;transition:all .15s;
  text-decoration:none}
.btn-primary{background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;
  box-shadow:0 2px 8px rgba(99,102,241,.25)}
.btn-primary:hover{box-shadow:0 4px 16px rgba(99,102,241,.4);transform:translateY(-1px)}
.btn-primary:active{transform:scale(.97)}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;transform:none;box-shadow:none}
.btn-ghost{background:transparent;color:#94a3b8;border:1px solid var(--border)}
.btn-ghost:hover{background:rgba(255,255,255,.04);color:#fff}
.btn-row{display:flex;gap:10px;margin-top:28px}
.btn-row .btn{flex:1}

/* Env checks */
.check-list{display:flex;flex-direction:column;gap:8px;margin-bottom:8px}
.check-item{display:flex;align-items:center;gap:12px;padding:12px 14px;
  background:var(--bg);border:1px solid var(--border);border-radius:12px;
  font-size:13px;transition:all .3s}
.check-item .ci-icon{width:20px;height:20px;border-radius:6px;display:grid;
  place-items:center;flex-shrink:0}
.check-item.ok .ci-icon{background:rgba(34,197,94,.1);color:var(--ok)}
.check-item.warn .ci-icon{background:rgba(245,158,11,.1);color:var(--warn)}
.check-item.err .ci-icon{background:rgba(239,68,68,.1);color:var(--err)}
.check-item .ci-label{flex:1;color:#cbd5e1}
.check-item .ci-label small{display:block;font-size:11px;color:var(--muted);margin-top:1px}
.check-item .ci-status{font-size:11px;font-weight:600}
.check-item.ok .ci-status{color:var(--ok)}
.check-item.warn .ci-status{color:var(--warn)}
.check-item.err .ci-status{color:var(--err)}

/* Feature pills */
.features{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:4px}
.feature{display:flex;align-items:center;gap:10px;padding:10px 12px;
  background:rgba(99,102,241,.04);border:1px solid rgba(99,102,241,.1);
  border-radius:10px;font-size:12px;color:#94a3b8}
.feature svg{width:16px;height:16px;color:var(--brand);flex-shrink:0}
.feature strong{color:#e2e8f0;font-weight:600}

/* Seed options */
.seed-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:4px}
.seed-option{padding:14px 12px;border-radius:12px;border:1px solid var(--border);
  background:var(--bg);cursor:pointer;transition:all .2s;text-align:center}
.seed-option:hover{border-color:rgba(99,102,241,.3);background:rgba(99,102,241,.04)}
.seed-option.selected{border-color:var(--brand);background:rgba(99,102,241,.08);
  box-shadow:0 0 0 2px rgba(99,102,241,.15)}
.seed-option .so-icon{width:32px;height:32px;margin:0 auto 8px;border-radius:10px;
  display:grid;place-items:center;font-size:16px}
.seed-option .so-label{font-size:13px;font-weight:600;color:#e2e8f0}
.seed-option .so-desc{font-size:11px;color:var(--muted);margin-top:2px}

/* Spinner */
.spinner{width:16px;height:16px;border:2px solid rgba(255,255,255,.3);
  border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* Success page */
.success-icon{width:64px;height:64px;margin:0 auto 20px;border-radius:20px;
  background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.15);
  display:grid;place-items:center}
.success-icon svg{width:32px;height:32px;color:var(--ok)}

/* Error banner */
.error-banner{margin-top:16px;padding:12px 14px;border-radius:12px;
  background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.15);
  font-size:12px;color:#fca5a5;display:none}
.error-banner.show{display:flex;align-items:flex-start;gap:8px}
.error-banner svg{width:16px;height:16px;color:var(--err);flex-shrink:0;margin-top:1px}

/* Step visibility */
.step-panel{display:none}
.step-panel.active{display:block;animation:fadeUp .3s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

/* Footer */
.footer{text-align:center;margin-top:24px;font-size:11px;color:#2a2d3a}

/* Responsive */
@media(max-width:540px){
  .wizard{padding:16px}
  .card{padding:24px}
  .features,.seed-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<div class="wizard">
  <div class="brand">
    <div class="brand-logo">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
    </div>
    <h1>Bangron Studio</h1>
    <p>Setup your backend platform</p>
  </div>

  <!-- Step indicators -->
  <div class="steps" id="steps">
    <div class="step-dot active" data-step="0">1</div>
    <div class="step-line"></div>
    <div class="step-dot" data-step="1">2</div>
    <div class="step-line"></div>
    <div class="step-dot" data-step="2">3</div>
    <div class="step-line"></div>
    <div class="step-dot" data-step="3">4</div>
  </div>

  <!-- ════ Step 0: Environment ════ -->
  <div class="step-panel active" id="panel-0">
    <div class="card">
      <h2>Environment Check</h2>
      <p class="subtitle">Verifying your server meets the requirements.</p>
      <div class="check-list">
        {$this->renderChecks($checks)}
      </div>
      <div class="error-banner" id="env-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <span>Some requirements are not met. Please fix them before continuing.</span>
      </div>
      <div class="btn-row">
        <button class="btn btn-primary" id="btn-env-next" {$this->checksPass($checks) ? '' : 'disabled'}>
          Continue
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- ════ Step 1: Admin Account ════ -->
  <div class="step-panel" id="panel-1">
    <div class="card">
      <h2>Create Admin Account</h2>
      <p class="subtitle">This will be your primary administrator account for managing the platform.</p>

      <div class="field">
        <label class="field-label">Username</label>
        <div class="field-input-wrap">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <input class="field-input" type="text" id="f-username" value="admin" placeholder="admin" autocomplete="off"/>
        </div>
        <div class="field-hint">Used to sign in to Bangron Studio</div>
      </div>

      <div class="field">
        <label class="field-label">Email Address</label>
        <div class="field-input-wrap">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          <input class="field-input" type="email" id="f-email" value="" placeholder="admin@example.com" autocomplete="off"/>
        </div>
      </div>

      <div class="field">
        <label class="field-label">Password</label>
        <div class="field-input-wrap">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input class="field-input" type="password" id="f-password" placeholder="Min. 8 characters" autocomplete="new-password"/>
          <button type="button" class="toggle-pass" id="toggle-pass">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="field-hint">Minimum 8 characters with mixed case recommended</div>
      </div>

      <div class="error-banner" id="account-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <span id="account-error-text"></span>
      </div>

      <div class="btn-row">
        <button class="btn btn-ghost" id="btn-account-back">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Back
        </button>
        <button class="btn btn-primary" id="btn-account-next">
          Continue
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- ════ Step 2: Database & Seed ════ -->
  <div class="step-panel" id="panel-2">
    <div class="card">
      <h2>Database & Starter Data</h2>
      <p class="subtitle">Choose a name for your primary database and optionally seed sample data.</p>

      <div class="field">
        <label class="field-label">Database Name</label>
        <div class="field-input-wrap">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
          <input class="field-input" type="text" id="f-dbname" value="app" placeholder="app" autocomplete="off"/>
        </div>
        <div class="field-hint">Your main application database. Collections: users, posts, tasks</div>
      </div>

      <div style="margin-top:20px">
        <label class="field-label">Seed Starter Collections</label>
        <div class="seed-grid">
          <div class="seed-option selected" data-seed="blog">
            <div class="so-icon" style="background:rgba(99,102,241,.1)">📝</div>
            <div class="so-label">Blog</div>
            <div class="so-desc">posts with schema</div>
          </div>
          <div class="seed-option selected" data-seed="tasks">
            <div class="so-icon" style="background:rgba(245,158,11,.1)">✅</div>
            <div class="so-label">Tasks</div>
            <div class="so-desc">task management</div>
          </div>
          <div class="seed-option" data-seed="products">
            <div class="so-icon" style="background:rgba(34,197,94,.1)">📦</div>
            <div class="so-label">Products</div>
            <div class="so-desc">e-commerce items</div>
          </div>
          <div class="seed-option" data-seed="users">
            <div class="so-icon" style="background:rgba(236,72,153,.1)">👥</div>
            <div class="so-label">Users</div>
            <div class="so-desc">user profiles</div>
          </div>
        </div>
      </div>

      <div class="error-banner" id="db-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <span id="db-error-text"></span>
      </div>

      <div class="btn-row">
        <button class="btn btn-ghost" id="btn-db-back">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Back
        </button>
        <button class="btn btn-primary" id="btn-launch">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>
          <span id="btn-launch-text">Create & Launch</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ════ Step 3: Done ════ -->
  <div class="step-panel" id="panel-3">
    <div class="card" style="text-align:center">
      <div class="success-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <h2 style="margin-bottom:8px">You're All Set!</h2>
      <p class="subtitle" style="max-width:340px;margin:0 auto 24px">
        Bangron Studio is configured and ready. Your admin account and database have been created.
      </p>
      <div class="features" style="max-width:300px;margin:0 auto 24px">
        <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg><strong>JWT Auth</strong></div>
        <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg><strong>RBAC</strong></div>
        <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg><strong>AES-256</strong></div>
        <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg><strong>SQLite</strong></div>
      </div>
      <a href="/" class="btn btn-primary" style="width:100%;justify-content:center">
        Go to Dashboard
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
  </div>

  <div class="footer">Bangron Studio v2.0.0 — Flight PHP + Inertia.js</div>
</div>

<script>
(function(){
  var currentStep = 0;
  var panels = document.querySelectorAll('.step-panel');
  var dots = document.querySelectorAll('.step-dot');
  var lines = document.querySelectorAll('.step-line');

  function goTo(n) {
    panels[currentStep].classList.remove('active');
    dots[currentStep].classList.remove('active');
    if(currentStep > 0) {
      dots[currentStep].classList.add('done');
      dots[currentStep].innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
      lines[currentStep-1].classList.add('done');
    }
    currentStep = n;
    panels[currentStep].classList.add('active');
    dots[currentStep].classList.add('active');
  }

  // Toggle password
  document.getElementById('toggle-pass').addEventListener('click', function(){
    var inp = document.getElementById('f-password');
    inp.type = inp.type === 'password' ? 'text' : 'password';
  });

  // Step 0 → 1
  document.getElementById('btn-env-next').addEventListener('click', function(){ goTo(1); });

  // Step 1 back
  document.getElementById('btn-account-back').addEventListener('click', function(){ goTo(0); });

  // Step 1 → 2
  document.getElementById('btn-account-next').addEventListener('click', function(){
    var u = document.getElementById('f-username').value.trim();
    var p = document.getElementById('f-password').value;
    var banner = document.getElementById('account-error');
    var text = document.getElementById('account-error-text');
    banner.classList.remove('show');

    if(!u){ text.textContent='Username is required'; banner.classList.add('show'); return; }
    if(u.length < 3){ text.textContent='Username must be at least 3 characters'; banner.classList.add('show'); return; }
    if(p.length < 8){ text.textContent='Password must be at least 8 characters'; banner.classList.add('show'); return; }
    goTo(2);
  });

  // Step 2 back
  document.getElementById('btn-db-back').addEventListener('click', function(){ goTo(1); });

  // Seed toggle
  document.querySelectorAll('.seed-option').forEach(function(el){
    el.addEventListener('click', function(){ el.classList.toggle('selected'); });
  });

  // Step 2 → Launch
  document.getElementById('btn-launch').addEventListener('click', async function(){
    var btn = this;
    var btnText = document.getElementById('btn-launch-text');
    var banner = document.getElementById('db-error');
    var bannerText = document.getElementById('db-error-text');
    banner.classList.remove('show');

    var dbName = document.getElementById('f-dbname').value.trim();
    if(!dbName){ bannerText.textContent='Database name is required'; banner.classList.add('show'); return; }
    if(!/^[a-z0-9_]+$/.test(dbName)){ bannerText.textContent='Only lowercase letters, numbers, and underscores'; banner.classList.add('show'); return; }

    var seeds = [];
    document.querySelectorAll('.seed-option.selected').forEach(function(el){ seeds.push(el.dataset.seed); });

    btn.disabled = true;
    btnText.textContent = 'Creating...';
    btn.querySelector('svg').outerHTML = '<div class="spinner"></div>';

    try {
      var body = {
        username: document.getElementById('f-username').value.trim(),
        email: document.getElementById('f-email').value.trim() || 'admin@bangron.studio',
        password: document.getElementById('f-password').value,
        app_db: dbName,
        seed: seeds
      };
      var r = await fetch('/api/setup/initialize', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(body)
      });
      var data = await r.json();
      if(data.ok) {
        goTo(3);
      } else {
        throw new Error(data.message || 'Setup failed');
      }
    } catch(e) {
      bannerText.textContent = e.message || 'Connection error. Is the server running?';
      banner.classList.add('show');
      btn.disabled = false;
      btnText.textContent = 'Create & Launch';
    }
  });

  // Enter key on password field
  document.getElementById('f-password').addEventListener('keydown', function(e){
    if(e.key === 'Enter') document.getElementById('btn-account-next').click();
  });
})();
</script>
</body>
</html>
HTML;
    }

    private function envChecks(): array
    {
        $checks = [];

        // PHP version
        $phpVer = PHP_VERSION;
        $phpOk = version_compare($phpVer, '8.1.0', '>=');
        $checks[] = [
            'label' => 'PHP Version',
            'detail' => $phpVer,
            'status' => $phpOk ? 'ok' : 'err',
            'statusText' => $phpOk ? 'OK' : 'Need 8.1+',
        ];

        // SQLite3
        $sqliteOk = extension_loaded('sqlite3') && extension_loaded('pdo_sqlite');
        $checks[] = [
            'label' => 'SQLite3 Extension',
            'detail' => $sqliteOk ? 'Loaded' : 'Missing',
            'status' => $sqliteOk ? 'ok' : 'err',
            'statusText' => $sqliteOk ? 'OK' : 'Required',
        ];

        // JSON
        $jsonOk = extension_loaded('json');
        $checks[] = [
            'label' => 'JSON Extension',
            'detail' => $jsonOk ? 'Loaded' : 'Missing',
            'status' => $jsonOk ? 'ok' : 'err',
            'statusText' => $jsonOk ? 'OK' : 'Required',
        ];

        // mbstring
        $mbOk = extension_loaded('mbstring');
        $checks[] = [
            'label' => 'mbstring',
            'detail' => $mbOk ? 'Loaded' : 'Missing',
            'status' => $mbOk ? 'ok' : 'warn',
            'statusText' => $mbOk ? 'OK' : 'Recommended',
        ];

        // OpenSSL
        $sslOk = extension_loaded('openssl');
        $checks[] = [
            'label' => 'OpenSSL',
            'detail' => $sslOk ? 'Loaded' : 'Missing',
            'status' => $sslOk ? 'ok' : 'warn',
            'statusText' => $sslOk ? 'OK' : 'Needed for JWT',
        ];

        // Storage writable
        $writable = is_writable($this->dbPath) || (!is_dir($this->dbPath) && is_writable(dirname($this->dbPath)));
        $checks[] = [
            'label' => 'Storage Writable',
            'detail' => $this->dbPath,
            'status' => $writable ? 'ok' : 'err',
            'statusText' => $writable ? 'OK' : 'Not writable',
        ];

        return $checks;
    }

    private function checksPass(array $checks): bool
    {
        foreach ($checks as $c) {
            if ($c['status'] === 'err') return false;
        }
        return true;
    }

    private function renderChecks(array $checks): string
    {
        $html = '';
        $icons = [
            'ok'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px"><polyline points="20 6 9 17 4 12"/></svg>',
            'warn' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            'err'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        ];

        foreach ($checks as $c) {
            $html .= sprintf(
                '<div class="check-item %s">
                  <div class="ci-icon">%s</div>
                  <div class="ci-label">%s<small>%s</small></div>
                  <div class="ci-status">%s</div>
                </div>',
                $c['status'],
                $icons[$c['status']] ?? '',
                htmlspecialchars($c['label']),
                htmlspecialchars($c['detail']),
                htmlspecialchars($c['statusText'])
            );
        }
        return $html;
    }
}