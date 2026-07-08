<?php
declare(strict_types=1);

/**
 * Application Bootstrap
 *
 * Loads environment, configures Flight, registers services,
 * sets up Monolog, and defines global helpers.
 */

use App\Inertia\Inertia;
use App\Services\BangronService;
use App\Logging\LoggerFactory;
use Monolog\Logger;

// ─── Environment ───────────────────────────────────────────────────────

if (file_exists(__DIR__ . '/../.env')) {
    foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $_ENV[trim($k)] = trim($v);
    }
}

// ─── Paths ────────────────────────────────────────────────────────────

$dbPath = $_ENV['DB_PATH'] ?? realpath(__DIR__ . '/../storage/data');
if (!is_dir($dbPath)) {
    @mkdir($dbPath, 0777, true);
}

// ─── Monolog ──────────────────────────────────────────────────────────

$logger = LoggerFactory::create();
$appLogDir = dirname(__DIR__) . '/storage/logs';
if (!is_dir($appLogDir)) {
    @mkdir($appLogDir, 0777, true);
}

// ─── Flight Services ──────────────────────────────────────────────────

Flight::register('bangron', BangronService::class, [$dbPath]);
Flight::register('inertia', Inertia::class);

// Store logger in Flight for global access
Flight::map('logger', function () use ($logger): Logger {
    return $logger;
});

// ─── Flight JSON Helper ───────────────────────────────────────────────

Flight::map('json', function ($data, int $code = 200): void {
    Flight::response()->status($code);
    Flight::response()->header('Content-Type', 'application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    Flight::stop();
});

// ─── Error Handler (with Monolog) ─────────────────────────────────────

Flight::map('error', function (\Throwable $e) use ($logger): void {
    $debug = ($_ENV['APP_DEBUG'] ?? 'true') === 'true';

    $logger->error('Unhandled exception: ' . $e->getMessage(), [
        'exception' => get_class($e),
        'file'      => $e->getFile() . ':' . $e->getLine(),
        'trace'     => $e->getTraceAsString(),
        'url'       => $_SERVER['REQUEST_URI'] ?? '',
        'method'    => $_SERVER['REQUEST_METHOD'] ?? '',
    ]);

    Flight::json([
        'error'   => true,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'trace'   => $debug ? explode("\n", $e->getTraceAsString()) : null,
    ], 500);
});

// ─── 404 Handler ──────────────────────────────────────────────────────

Flight::map('notFound', function () use ($logger): void {
    $logger->warning('404 Not Found', [
        'url'    => $_SERVER['REQUEST_URI'] ?? '',
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    ]);
    Flight::json(['error' => true, 'message' => 'Halaman tidak ditemukan'], 404);
});

// ─── Route Files ──────────────────────────────────────────────────────

require_once __DIR__ . '/Http/Routes/web.php';
require_once __DIR__ . '/Http/Routes/auth.php';
require_once __DIR__ . '/Http/Routes/api.php';
require_once __DIR__ . '/Http/Routes/admin.php';