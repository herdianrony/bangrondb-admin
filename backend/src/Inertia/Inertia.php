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

        // Resolve production build assets from manifest
        $manifestPath = dirname(__DIR__, 2) . '/public/build/.vite/manifest.json';
        $jsFile = '';
        $cssFile = '';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $entry = $manifest['src/main.js'] ?? [];
            $jsFile = $entry['file'] ?? '';
            $cssFile = $entry['css'][0] ?? '';
        }

        $pageJson = htmlspecialchars(json_encode($page), ENT_QUOTES, 'UTF-8');

        // Build CSS link
        $cssTag = $cssFile ? '<link rel="stylesheet" href="/build/' . $cssFile . '">' : '';

        // Build JS — production build with Vite dev fallback
        if ($jsFile) {
            $jsTag = '<script type="module" src="/build/' . $jsFile . '"></script>';
        } else {
            // No build found — try Vite dev server
            $jsTag = '<script type="module">
try {
  await import("http://localhost:5173/@vite/client");
  await import("http://localhost:5173/src/main.js");
} catch(e) { console.warn("Vite dev server not running", e); }
</script>';
        }

        echo <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Bangron Studio</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
{$cssTag}
<style>
/* Minimal loading state — replaced by Vue app */
:root{color-scheme:dark;--bg:#0f1117;--panel:#161922;--accent:#6366f1}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',system-ui,-apple-system,sans-serif;background:var(--bg);color:#e2e8f0;overflow:hidden;height:100vh}
#app{display:flex;height:100vh}
.sidebar-skeleton{width:260px;background:var(--panel);border-right:1px solid rgba(255,255,255,.07);flex-shrink:0;padding:20px;display:flex;flex-direction:column;gap:12px}
.sidebar-skeleton .brand-s{width:140px;height:20px;background:rgba(255,255,255,.06);border-radius:8px}
.sidebar-skeleton .mode-s{width:100%;height:36px;background:rgba(255,255,255,.03);border-radius:12px;border:1px solid rgba(255,255,255,.07)}
.sidebar-skeleton .nav-s{flex:1;display:flex;flex-direction:column;gap:4px}
.sidebar-skeleton .nav-item-s{height:36px;background:rgba(255,255,255,.03);border-radius:10px}
.sidebar-skeleton .nav-item-s.active{background:rgba(99,102,241,.1)}
.sidebar-skeleton .footer-s{width:80px;height:14px;background:rgba(255,255,255,.04);border-radius:6px}
.main-skeleton{flex:1;padding:32px;display:flex;flex-direction:column;gap:16px;overflow:auto}
.main-skeleton .header-s{display:flex;align-items:center;gap:12px}
.main-skeleton .header-icon{width:36px;height:36px;border-radius:12px;background:rgba(99,102,241,.15)}
.main-skeleton .header-title{width:180px;height:28px;background:rgba(255,255,255,.06);border-radius:8px}
.main-skeleton .kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.main-skeleton .kpi-s{height:88px;background:rgba(22,25,34,.8);border:1px solid rgba(255,255,255,.07);border-radius:16px}
.main-skeleton .card-s{height:200px;background:rgba(22,25,34,.8);border:1px solid rgba(255,255,255,.07);border-radius:16px}
@keyframes pulse{0%,100%{opacity:.4}50%{opacity:.7}}
.sidebar-skeleton div,.main-skeleton .header-title,.main-skeleton .kpi-s,.main-skeleton .card-s{animation:pulse 1.5s ease-in-out infinite}
@media(max-width:1023px){.sidebar-skeleton{display:none}.main-skeleton .kpi-row{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<div id="app" data-page="{$pageJson}">
  <div class="sidebar-skeleton">
    <div class="brand-s"></div>
    <div class="mode-s"></div>
    <div class="nav-s">
      <div class="nav-item-s active"></div>
      <div class="nav-item-s"></div>
      <div class="nav-item-s"></div>
      <div class="nav-item-s"></div>
      <div class="nav-item-s"></div>
      <div class="nav-item-s"></div>
      <div class="nav-item-s"></div>
    </div>
    <div class="footer-s"></div>
  </div>
  <div class="main-skeleton">
    <div class="header-s">
      <div class="header-icon"></div>
      <div class="header-title"></div>
    </div>
    <div class="kpi-row">
      <div class="kpi-s"></div>
      <div class="kpi-s"></div>
      <div class="kpi-s"></div>
      <div class="kpi-s"></div>
    </div>
    <div class="card-s"></div>
  </div>
</div>
{$jsTag}
</body>
</html>
HTML;
    }
}