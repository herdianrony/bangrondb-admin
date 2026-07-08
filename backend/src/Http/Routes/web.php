<?php
declare(strict_types=1);

/**
 * Web / Inertia page routes.
 *
 * Pages are rendered by Vue SPA. Flight only serves:
 *   1. Initial HTML shell with Inertia data (GET /)
 *   2. SPA catch-all (GET /@path) — lets Vue router handle everything
 */

use App\Controllers\InertiaController;

// Entry point — dashboard or setup wizard
Flight::route('GET /', [InertiaController::class, 'index']);

// SPA catch-all — Vue handles client-side routing
Flight::route('GET /@path', [InertiaController::class, 'page']);