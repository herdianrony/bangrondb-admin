<?php
declare(strict_types=1);

/**
 * Bangron Studio — Entry Point
 *
 * Slim entry: CORS middleware → bootstrap → Flight::start()
 * All routes, controllers, and middleware are loaded via bootstrap.php.
 */

require __DIR__ . '/../vendor/autoload.php';

// ─── CORS (before everything) ─────────────────────────────────────────

\App\Http\Middleware\CorsMiddleware::handle();

// ─── Bootstrap: env, services, Monolog, routes ────────────────────────

require __DIR__ . '/../src/bootstrap.php';

// ─── Start ────────────────────────────────────────────────────────────

Flight::start();