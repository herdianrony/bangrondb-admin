<?php
declare(strict_types=1);

/**
 * Web / Inertia page routes.
 */

use App\Controllers\InertiaController;

// Dashboard
Flight::route('GET /', [InertiaController::class, 'index']);

// SPA catch-all for Inertia pages
Flight::route('GET /app/@path', [InertiaController::class, 'page']);