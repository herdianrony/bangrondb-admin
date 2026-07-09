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
use App\Controllers\WebAuthController;

// Entry point — dashboard or setup wizard
Flight::route('GET /', [InertiaController::class, 'index']);

// Setup
Flight::route('GET /setup', [InertiaController::class, 'setup']);

// Auth pages – SESSION based (Web)
Flight::route('GET  /auth/login', [WebAuthController::class, 'showLogin']);
Flight::route('POST /login', [WebAuthController::class, 'login']);
Flight::route('POST /logout', [WebAuthController::class, 'logout']);
Flight::route('GET  /auth/me', [WebAuthController::class, 'me']);

// keep old register page
Flight::route('GET /auth/register', [InertiaController::class, 'authRegister']);

// Admin Studio pages
Flight::route('GET /users', function(){ \Flight::inertia()->render('Users/Index', []); });
Flight::route('GET /roles', function(){ \Flight::inertia()->render('Roles/Index', []); });
Flight::route('GET /permissions', function(){ \Flight::inertia()->render('Permissions/Index', []); });
Flight::route('GET /tokens', function(){ \Flight::inertia()->render('Tokens/Index', []); });
Flight::route('GET /acl', function(){ \Flight::inertia()->render('Acl/Index', []); });

// Database detail — shows collections inside a database
Flight::route('GET /databases/@db', [InertiaController::class, 'database']);

// Collection detail — shows documents inside a collection
Flight::route('GET /databases/@db/collections/@col', [InertiaController::class, 'collection']);

// Catch-all fallback — 404 via Inertia
Flight::route('GET /@path', [InertiaController::class, 'fallback']);