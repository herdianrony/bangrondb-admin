<?php
declare(strict_types=1);

/**
 * Simplified REST-like API routes.
 *
 * Structure:
 *   /databases                              GET (list), POST (create)
 *   /databases/@db                          DELETE (drop), POST rename
 *   /databases/@db/health                   GET
 *   /databases/@db/metrics                  GET
 *   /databases/@db/vacuum                   POST
 *   /databases/@db/transaction              POST
 *   /databases/@db/indexes                  GET, DELETE /@name
 *
 *   /databases/@db/collections              GET (list), POST (create)
 *   /databases/@db/collections/@col         DELETE (drop), POST rename
 *
 *   /databases/@db/collections/@col/documents       GET, POST
 *   /databases/@db/collections/@col/documents/@id   GET, PUT, DELETE
 *   /databases/@db/collections/@col/query           POST
 *   /databases/@db/collections/@col/count           POST
 *   /databases/@db/collections/@col/save            POST (upsert)
 *
 *   /databases/@db/collections/@col/schema          GET, POST
 *   /databases/@db/collections/@col/schema/validate POST
 *
 *   /databases/@db/collections/@col/acl             GET, PUT
 *   /databases/@db/collections/@col/acl/test        POST
 *
 *   /databases/@db/collections/@col/config          GET
 *   /databases/@db/collections/@col/config/save     POST
 *   /databases/@db/collections/@col/id-mode         POST
 *
 *   /databases/@db/collections/@col/encryption      POST
 *   /databases/@db/collections/@col/soft-deletes    POST (toggle)
 *   /databases/@db/collections/@col/restore         POST
 *   /databases/@db/collections/@col/force-delete     POST
 *   /databases/@db/collections/@col/hooks           GET
 *   /databases/@db/collections/@col/populate        POST
 *   /databases/@db/collections/@col/indexes         POST (create)
 *
 *   /setup/status                                    GET
 *   /setup/initialize                                POST
 *
 *   /audit/logs                                      GET
 *   /audit/stats                                     GET
 *
 *   /status                                          GET
 */

use App\Controllers\DatabaseController;
use App\Controllers\CollectionController;
use App\Controllers\DocumentController;
use App\Controllers\SchemaController;
use App\Controllers\EncryptionController;
use App\Controllers\AclController;
use App\Controllers\AuditController;
use App\Controllers\SoftDeleteController;
use App\Controllers\HookController;
use App\Controllers\RelationController;
use App\Controllers\IndexController;
use App\Controllers\HealthController;
use App\Controllers\ConfigController;
use App\Controllers\SetupController;
use App\Controllers\StatusController;

// ═══════════════════════════════════════════════════════════════
// Databases
// ═══════════════════════════════════════════════════════════════
Flight::route('GET    /databases',                        [DatabaseController::class, 'index']);
Flight::route('POST   /databases',                        [DatabaseController::class, 'store']);
Flight::route('DELETE /databases/@name',                  [DatabaseController::class, 'destroy']);
Flight::route('POST   /databases/@name/rename',           [DatabaseController::class, 'rename']);

// DB-level: health, metrics, vacuum, transaction, indexes
Flight::route('GET    /databases/@db/health',             [HealthController::class, 'show']);
Flight::route('GET    /databases/@db/metrics',            [HealthController::class, 'metrics']);
Flight::route('POST   /databases/@db/vacuum',             [HealthController::class, 'vacuum']);
Flight::route('POST   /databases/@db/transaction',        [ConfigController::class, 'transaction']);
Flight::route('GET    /databases/@db/indexes',            [IndexController::class, 'index']);
Flight::route('DELETE /databases/@db/indexes/@name',      [IndexController::class, 'destroy']);

// ═══════════════════════════════════════════════════════════════
// Collections (under a database)
// ═══════════════════════════════════════════════════════════════
Flight::route('GET    /databases/@db/collections',                    [CollectionController::class, 'index']);
Flight::route('POST   /databases/@db/collections',                    [CollectionController::class, 'store']);
Flight::route('DELETE /databases/@db/collections/@col',               [CollectionController::class, 'destroy']);
Flight::route('POST   /databases/@db/collections/@col/rename',        [CollectionController::class, 'rename']);

// ═══════════════════════════════════════════════════════════════
// Documents (under a collection)
// ═══════════════════════════════════════════════════════════════
Flight::route('GET    /databases/@db/collections/@col/documents',            [DocumentController::class, 'index']);
Flight::route('POST   /databases/@db/collections/@col/documents',            [DocumentController::class, 'store']);
Flight::route('GET    /databases/@db/collections/@col/documents/@id',        [DocumentController::class, 'show']);
Flight::route('PUT    /databases/@db/collections/@col/documents/@id',        [DocumentController::class, 'update']);
Flight::route('DELETE /databases/@db/collections/@col/documents',            [DocumentController::class, 'destroy']);
Flight::route('POST   /databases/@db/collections/@col/save',                  [DocumentController::class, 'save']);
Flight::route('POST   /databases/@db/collections/@col/count',                 [DocumentController::class, 'count']);
Flight::route('POST   /databases/@db/collections/@col/query',                 [DocumentController::class, 'query']);

// ═══════════════════════════════════════════════════════════════
// Collection sub-resources (schema, acl, config, encryption, etc)
// ═══════════════════════════════════════════════════════════════

// Schema
Flight::route('GET    /databases/@db/collections/@col/schema',                [SchemaController::class, 'show']);
Flight::route('POST   /databases/@db/collections/@col/schema',                [SchemaController::class, 'store']);
Flight::route('POST   /databases/@db/collections/@col/schema/validate',       [SchemaController::class, 'validate']);

// ACL
Flight::route('GET    /databases/@db/collections/@col/acl',                   [AclController::class, 'show']);
Flight::route('PUT    /databases/@db/collections/@col/acl',                   [AclController::class, 'store']);
Flight::route('POST   /databases/@db/collections/@col/acl/test',              [AclController::class, 'test']);

// Config
Flight::route('GET    /databases/@db/collections/@col/config',                [ConfigController::class, 'show']);
Flight::route('POST   /databases/@db/collections/@col/config/save',           [ConfigController::class, 'save']);
Flight::route('POST   /databases/@db/collections/@col/id-mode',               [ConfigController::class, 'idMode']);

// Encryption
Flight::route('POST   /databases/@db/collections/@col/encryption',            [EncryptionController::class, 'store']);

// Soft Deletes
Flight::route('POST   /databases/@db/collections/@col/soft-deletes',          [SoftDeleteController::class, 'toggle']);
Flight::route('POST   /databases/@db/collections/@col/restore',               [SoftDeleteController::class, 'restore']);
Flight::route('POST   /databases/@db/collections/@col/force-delete',          [SoftDeleteController::class, 'forceDelete']);

// Hooks
Flight::route('GET    /databases/@db/collections/@col/hooks',                  [HookController::class, 'index']);

// Populate / Relations
Flight::route('POST   /databases/@db/collections/@col/populate',              [RelationController::class, 'populate']);

// Indexes (collection-level: create)
Flight::route('POST   /databases/@db/collections/@col/indexes',               [IndexController::class, 'store']);

// ═══════════════════════════════════════════════════════════════
// Setup
// ═══════════════════════════════════════════════════════════════
Flight::route('GET    /setup/status',                       [SetupController::class, 'status']);
Flight::route('POST   /setup/initialize',                   [SetupController::class, 'initialize']);

// ═══════════════════════════════════════════════════════════════
// Audit
// ═══════════════════════════════════════════════════════════════
Flight::route('GET    /audit/logs',                         [AuditController::class, 'index']);
Flight::route('GET    /audit/stats',                        [AuditController::class, 'stats']);

// ═══════════════════════════════════════════════════════════════
// Status
// ═══════════════════════════════════════════════════════════════
Flight::route('GET    /status',                             [StatusController::class, 'index']);

// ═══════════════════════════════════════════════════════════════
// SSOT BC shim (deprecated)
// ═══════════════════════════════════════════════════════════════
Flight::route('GET    /ssot/@db/@col',                     [StatusController::class, 'ssotGet']);
Flight::route('PUT    /ssot/@db/@col',                     [StatusController::class, 'ssotPut']);
Flight::route('POST   /ssot/@db/@col/@any',                [StatusController::class, 'ssotAny']);
Flight::route('GET    /ssot/@any',                         [StatusController::class, 'ssotRoot']);