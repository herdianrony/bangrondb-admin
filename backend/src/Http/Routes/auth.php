<?php
declare(strict_types=1);

/**
 * Authentication routes: register, login, refresh, logout, revoke, blacklist, tokens, me.
 */

use App\Controllers\AuthController;

Flight::route('POST /api/auth/register',  [AuthController::class, 'register']);
Flight::route('POST /api/auth/login',     [AuthController::class, 'login']);
Flight::route('POST /api/auth/refresh',   [AuthController::class, 'refresh']);
Flight::route('POST /api/auth/logout',    [AuthController::class, 'logout']);
Flight::route('POST /api/auth/revoke',    [AuthController::class, 'revoke']);
Flight::route('GET  /api/auth/blacklist', [AuthController::class, 'blacklist']);
Flight::route('GET  /api/auth/tokens',   [AuthController::class, 'tokens']);
Flight::route('GET  /api/auth/me',       [AuthController::class, 'me']);