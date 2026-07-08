<?php
declare(strict_types=1);

/**
 * Authentication routes.
 *
 * /auth/register   POST
 * /auth/login      POST
 * /auth/refresh    POST
 * /auth/logout     POST
 * /auth/revoke     POST
 * /auth/blacklist  GET
 * /auth/tokens     GET
 * /auth/me         GET
 */

use App\Controllers\AuthController;

Flight::route('POST /auth/register',  [AuthController::class, 'register']);
Flight::route('POST /auth/login',     [AuthController::class, 'login']);
Flight::route('POST /auth/refresh',   [AuthController::class, 'refresh']);
Flight::route('POST /auth/logout',    [AuthController::class, 'logout']);
Flight::route('POST /auth/revoke',    [AuthController::class, 'revoke']);
Flight::route('GET  /auth/blacklist', [AuthController::class, 'blacklist']);
Flight::route('GET  /auth/tokens',   [AuthController::class, 'tokens']);
Flight::route('GET  /auth/me',       [AuthController::class, 'me']);