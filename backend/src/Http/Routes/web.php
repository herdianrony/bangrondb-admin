<?php
declare(strict_types=1);

/**
 * Web / Inertia page routes.
 */

use App\Controllers\InertiaController;

// Dashboard (also serves setup wizard if not initialized)
Flight::route('GET /', [InertiaController::class, 'index']);

// Setup wizard (direct access, redirects to / if already set up)
Flight::route('GET /setup', [InertiaController::class, 'index']);

// SPA catch-all for Inertia pages
Flight::route('GET /app/@path', [InertiaController::class, 'page']);