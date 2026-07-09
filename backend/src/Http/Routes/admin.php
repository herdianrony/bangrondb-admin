<?php
declare(strict_types=1);

/**
 * Admin routes: users & roles management, ACL check, token revoke.
 *
 * /admin/users                          GET (list), POST (create)
 * /admin/users/@id                      PUT (update), DELETE
 * /admin/users/@id/reset-password       POST
 * /admin/users/@id/toggle-active        POST
 * /admin/users/@id/revoke-tokens        POST
 *
 * /admin/roles                          GET (list), POST (create)
 * /admin/roles/@name                    PUT (update), DELETE
 *
 * /admin/acl/check                      POST
 */

use App\Controllers\AdminController;
use App\Controllers\PermissionController;

// ---------- Users Management ----------
Flight::route('GET    /admin/users',                            [AdminController::class, 'users']);
Flight::route('POST   /admin/users',                            [AdminController::class, 'createUser']);
Flight::route('PUT    /admin/users/@id',                        [AdminController::class, 'updateUser']);
Flight::route('DELETE /admin/users/@id',                        [AdminController::class, 'deleteUser']);
Flight::route('POST   /admin/users/@id/reset-password',         [AdminController::class, 'resetPassword']);
Flight::route('POST   /admin/users/@id/toggle-active',          [AdminController::class, 'toggleActive']);

// ---------- Roles Management ----------
Flight::route('GET    /admin/roles',                            [AdminController::class, 'roles']);
Flight::route('POST   /admin/roles',                            [AdminController::class, 'createRole']);
Flight::route('PUT    /admin/roles/@name',                      [AdminController::class, 'updateRole']);
Flight::route('DELETE /admin/roles/@name',                      [AdminController::class, 'deleteRole']);

// ---------- Permission Matrix Test ----------
Flight::route('POST   /admin/acl/check',                        [AdminController::class, 'aclCheck']);

// ---------- Permissions Management ----------
Flight::route('GET    /admin/permissions',                      [PermissionController::class, 'index']);
Flight::route('POST   /admin/permissions',                      [PermissionController::class, 'store']);
Flight::route('PUT    /admin/permissions/@name',                [PermissionController::class, 'update']);
Flight::route('DELETE /admin/permissions/@name',                [PermissionController::class, 'destroy']);

// ---------- Token Admin ----------
Flight::route('POST   /admin/users/@id/revoke-tokens',          [AdminController::class, 'revokeTokens']);