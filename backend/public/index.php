<?php
declare(strict_types=1);

/**
 * Bangron Studio — Entry Point
 *
 * Static file serving → CORS → bootstrap → Flight::start()
 */

// ─── Serve static files (works without .htaccess / on Nginx / built-in server) ──

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$staticFile = __DIR__ . $uri;

if ($uri !== '/' && file_exists($staticFile) && !is_dir($staticFile)) {
    $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
    $mimeMap = [
        'js'   => 'application/javascript',
        'css'  => 'text/css',
        'json' => 'application/json',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ico'  => 'image/x-icon',
        'html' => 'text/html',
    ];
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Length: ' . filesize($staticFile));
    readfile($staticFile);
    exit;
}

// ─── Autoload ────────────────────────────────────────────────────────────────

require __DIR__ . '/../vendor/autoload.php';

// ─── CORS (before everything) ──────────────────────────────────────────────

\App\Http\Middleware\CorsMiddleware::handle();

// ─── Bootstrap: env, services, Monolog, routes ──────────────────────────────

require __DIR__ . '/../src/bootstrap.php';

// ─── Start ──────────────────────────────────────────────────────────────────

Flight::start();