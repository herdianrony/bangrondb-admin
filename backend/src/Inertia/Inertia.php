<?php
declare(strict_types=1);

namespace App\Inertia;

class Inertia
{
    public function render(string $component, array $props = []): void
    {
        $isInertia = isset($_SERVER['HTTP_X_INERTIA']) && $_SERVER['HTTP_X_INERTIA'] === 'true';
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        $version = $_ENV['INERTIA_VERSION'] ?? '1';

        // Version mismatch handling
        if ($isInertia && isset($_SERVER['HTTP_X_INERTIA_VERSION']) && $_SERVER['HTTP_X_INERTIA_VERSION'] !== $version) {
            header('X-Inertia-Location: ' . $url, true, 409);
            exit;
        }

        $page = [
            'component' => $component,
            'props' => $props,
            'url' => $url,
            'version' => $version,
        ];

        if ($isInertia) {
            header('Vary: X-Inertia');
            header('X-Inertia: true');
            header('Content-Type: application/json');
            echo json_encode($page, JSON_UNESCAPED_UNICODE);
            return;
        }

        // Initial page load – render root blade-like template
        $pageJson = htmlspecialchars(json_encode($page), ENT_QUOTES, 'UTF-8');
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $viteDev = ($_ENV['APP_DEBUG'] ?? 'true') === 'true';

        echo <<<HTML
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Bangron Studio</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f1117;--panel:#161922;--muted:#8b93a7;--text:#e9eeff;--brand:#6366f1;--brand2:#8a5cff;--ok:#21cc8d;--warn:#ffb020;--err:#ff5b6b;--border:#1f2952}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:"Plus Jakarta Sans",system-ui,Inter,Segoe UI,Roboto,Arial,sans-serif}
a{color:var(--brand);text-decoration:none}
.app{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
.sidebar{background:rgba(22,25,34,.7);backdrop-filter:blur(8px);border-right:1px solid var(--border);padding:20px;position:sticky;top:0;height:100vh;overflow:auto}
.brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:18px;margin-bottom:18px}
.brand .logo{width:36px;height:36px;border-radius:12px;background:linear-gradient(135deg,var(--brand),var(--brand2));display:grid;place-items:center;font-weight:800}
.nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:#c9d3ef;margin:4px 0}
.nav a.active,.nav a:hover{background:#151f44;color:#fff}
.main{padding:28px}
.card{background:rgba(22,25,34,.9);border:1px solid var(--border);border-radius:18px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.25)}
.grid{display:grid;gap:16px}
.grid-3{grid-template-columns:repeat(3,1fr)}
.grid-2{grid-template-columns:repeat(2,1fr)}
.grid-4{grid-template-columns:repeat(4,1fr)}
@media(max-width:980px){.app{grid-template-columns:1fr}.sidebar{position:relative;height:auto}.grid-3,.grid-2,.grid-4{grid-template-columns:1fr}}
.kpi h3{margin:0;font-size:13px;color:var(--muted);font-weight:500}
.kpi .v{font-size:28px;font-weight:700;margin-top:6px}
.btn{background:linear-gradient(135deg,var(--brand),var(--brand2));border:0;color:white;padding:10px 14px;border-radius:12px;font-weight:600;cursor:pointer}
.btn-ghost{background:transparent;border:1px solid var(--border);color:#d7e0ff}
.input, select, textarea{width:100%;background:#0f1117;border:1px solid var(--border);color:#e9eeff;padding:10px 12px;border-radius:12px;outline:none}
label{font-size:12px;color:var(--muted);display:block;margin-bottom:6px}
table{width:100%;border-collapse:collapse}
th,td{padding:10px 12px;border-bottom:1px solid #1b2450;text-align:left;font-size:14px}
th{color:#aab4d6;font-weight:600}
.badge{padding:4px 8px;border-radius:999px;background:#16204a;border:1px solid #25336d;font-size:12px}
.muted{color:var(--muted)}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;gap:12px;flex-wrap:wrap}
.tag{font-size:11px;color:#9fb3ff;background:#1a2452;padding:4px 8px;border-radius:8px;border:1px solid #2a3a7a}
pre{background:#0b0d14;border:1px solid var(--border);padding:12px;border-radius:12px;overflow:auto;font-size:12px}
footer{color:var(--muted);font-size:12px;margin-top:24px}
.chips{display:flex;gap:8px;flex-wrap:wrap}
.chip{border:1px solid var(--border);padding:6px 10px;border-radius:999px;font-size:12px;background:#121c41}
</style>
</head>
<body>
<div id="app" data-page="{$pageJson}"></div>
<script type="module">
/* Minimal Inertia-like boot without build step (fallback). 
   If Vite dev server is running, swap to real Inertia app. */
const el = document.getElementById('app');
const page = JSON.parse(el.dataset.page || '{}');

function h(tag, attrs={}, children=[]) { const n=document.createElement(tag); Object.entries(attrs).forEach(([k,v])=>{ if(k==='class') n.className=v; else if(k.startsWith('on')) n.addEventListener(k.slice(2),v); else n.setAttribute(k,v)}); (Array.isArray(children)?children:[children]).forEach(c=> n.append(c?.nodeType?c:document.createTextNode(c??''))); return n; }

const menu = [
  ['Dashboard','/','📊'],
  ['Databases','/app/databases','🗄️'],
  ['Collections','/app/collections','📁'],
  ['Documents','/app/documents','📄'],
  ['Query Builder','/app/query','🔎'],
  ['Encryption','/app/encryption','🔐'],
  ['Schema','/app/schema','🧩'],
  ['Soft Deletes','/app/soft-deletes','🗑️'],
  ['Hooks','/app/hooks','🪝'],
  ['Relations','/app/relations','🔗'],
  ['Indexes','/app/indexes','⚡'],
  ['Health','/app/health','❤️‍🩹'],
  ['Config','/app/config','⚙️'],
];

const s = page.props?.stats || {};
const root = h('div',{class:'app'},[
  h('aside',{class:'sidebar'},[
    h('div',{class:'brand'},[
      h('div',{class:'logo'},'B'),
      h('span',{},'Bangron Studio')
    ]),
    h('div',{class:'muted',style:'font-size:12px;margin-bottom:12px'},'Flight PHP + Inertia.js'),
    h('nav',{class:'nav'}, menu.map(([label,href,icon])=>{
      const a = h('a',{href}, [icon+'  '+label]);
      if(location.pathname===href || location.pathname.startsWith(href)&&href!=='/') a.classList.add('active');
      return a;
    })),
    h('div',{style:'margin-top:18px'},[
      h('div',{class:'tag'}, 'v2.0.0 • SQLite • AES-256-GCM'),
    ]),
    h('footer',{}, 'Bangron Studio © herdianrony')
  ]),
  h('main',{class:'main'},[
    h('div',{class:'topbar'},[
      h('div',{}, [
        h('h1',{style:'margin:0'}, page.component?.split('/')?.[0] || 'Dashboard'),
        h('div',{class:'muted'}, 'Dynamic Admin • Flight + Inertia')
      ]),
      h('div',{class:'chips'}, [
        h('span',{class:'chip'}, 'DBs: '+(s.databases??'–')),
        h('span',{class:'chip'}, 'Size: '+(s.total_size_mb ?? 0)+' MB'),
        h('span',{class:'chip'}, 'PHP '+(s.php_version||'8.1+'))
      ])
    ]),
    h('div',{class:'grid grid-4'},[
      ...[
        ['Databases', s.databases ?? 0, '🗄️'],
        ['Total Collections', s.collections ?? 0, '📁'],
        ['Documents', s.documents ?? 0, '📄'],
        ['Health', (s.health?.status||'ok'), '💚'],
      ].map(([t,v,ic]) => h('div',{class:'card kpi'},[h('h3',{},ic+' '+t), h('div',{class:'v'}, String(v))]))
    ]),
    h('div',{class:'grid grid-2',style:'margin-top:16px'},[
      h('div',{class:'card'},[
        h('h3',{style:'margin-top:0'}, 'Quick Actions'),
        h('div',{style:'display:flex;gap:8px;flex-wrap:wrap;margin-top:10px'},[
          ['Buat Database','/app/databases'],
          ['Buka Collections','/app/collections'],
          ['Query Builder','/app/query'],
          ['Health Check','/app/health'],
        ].map(([l,href])=>{ const a=h('a',{class:'btn',href},l); return a;}))
      ]),
      h('div',{class:'card'},[
        h('h3',{style:'margin-top:0'}, 'Bangron Studio Features'),
        h('div',{class:'chips',style:'margin-top:8px'}, 'CRUD • Query Ops • Encryption • Schema • SoftDelete • Hooks • Populate • Index • Health • Config • Transactions'.split(' • ').map(x=>h('span',{class:'badge'},x)))
      ])
    ]),
    h('div',{class:'card',style:'margin-top:16px'},[
      h('h3',{style:'margin-top:0'}, 'API Studio'),
      h('div',{class:'muted',style:'margin-bottom:10px'}, 'All API endpoints ready. Inertia (Vue) frontend is at /frontend.'),
      h('pre',{}, JSON.stringify(page, null, 2))
    ])
  ])
]);
el.replaceWith(root);

// Try to load real Inertia Vue app if Vite is running
if (location.hostname === 'localhost') {
  const s = document.createElement('script');
  s.type='module';
  s.innerHTML = "import('http://localhost:5173/@vite/client').then(()=>import('http://localhost:5173/src/main.js')).catch(()=>{});";
  document.head.appendChild(s);
}
</script>
</body>
</html>
HTML;
    }
}
