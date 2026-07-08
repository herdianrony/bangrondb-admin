<?php
declare(strict_types=1);

/**
 * Web / Inertia page routes.
 *
 * Database-centric RESTful pattern:
 *   GET /                                        → Dashboard / Setup
 *   GET /setup                                   → Setup wizard
 *   GET /auth/login                              → Login page
 *   GET /auth/register                           → Register page
 *   GET /databases/@db                           → Database detail (collections)
 *   GET /databases/@db/collections/@col          → Collection detail (documents)
 *
 * No conflict with API routes (different segment counts / HTTP methods).
 */

use App\Controllers\InertiaController;

// Entry point — dashboard or setup wizard
Flight::route('GET /', [InertiaController::class, 'index']);

// Setup
Flight::route('GET /setup', [InertiaController::class, 'setup']);

// Auth pages
Flight::route('GET /auth/login', [InertiaController::class, 'authLogin']);
Flight::route('GET /auth/register', [InertiaController::class, 'authRegister']);

// Database detail — shows collections inside a database
Flight::route('GET /databases/@db', [InertiaController::class, 'database']);

// Collection detail — shows documents inside a collection
Flight::route('GET /databases/@db/collections/@col', [InertiaController::class, 'collection']);

// Catch-all fallback — 404 via Inertia
Flight::route('GET /@path', [InertiaController::class, 'fallback']);