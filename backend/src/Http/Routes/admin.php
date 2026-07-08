<?php
declare(strict_types=1);

/**
 * Admin routes: users & roles management, ACL check, token revoke.
 */

use App\Controllers\AdminController;

// ---------- Users Management ----------
Flight::route('GET  /api/admin/users',                              [AdminController::class, 'users']);
Flight::route('POST /api/admin/users',                              [AdminController::class, 'createUser']);
Flight::route('PUT  /api/admin/users/@id',                          [AdminController::class, 'updateUser']);
Flight::route('DELETE /api/admin/users/@id',                        [AdminController::class, 'deleteUser']);
Flight::route('POST /api/admin/users/@id/reset-password',           [AdminController::class, 'resetPassword']);
Flight::route('POST /api/admin/users/@id/toggle-active',            [AdminController::class, 'toggleActive']);

// ---------- Roles Management ----------
Flight::route('GET    /api/admin/roles',                            [AdminController::class, 'roles']);
Flight::route('POST   /api/admin/roles',                            [AdminController::class, 'createRole']);
Flight::route('PUT    /api/admin/roles/@name',                      [AdminController::class, 'updateRole']);
Flight::route('DELETE /api/admin/roles/@name',                      [AdminController::class, 'deleteRole']);

// ---------- Permission Matrix Test ----------
Flight::route('POST /api/admin/acl/check',                          [AdminController::class, 'aclCheck']);

// ---------- Token Admin ----------
Flight::route('POST /api/admin/users/@id/revoke-tokens',            [AdminController::class, 'revokeTokens']);