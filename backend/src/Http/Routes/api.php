<?php
declare(strict_types=1);

/**
 * Main data API routes: databases, collections, documents, query,
 * encryption, schema, ACL, audit, soft-deletes, hooks, relations,
 * indexes, health, config, transaction, setup, status, SSOT BC shim.
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

// ---------- Databases ----------
Flight::route('GET   /api/databases',               [DatabaseController::class, 'index']);
Flight::route('POST  /api/databases',               [DatabaseController::class, 'store']);
Flight::route('DELETE /api/databases/@name',        [DatabaseController::class, 'destroy']);
Flight::route('POST  /api/databases/@old/rename',   [DatabaseController::class, 'rename']);

// ---------- Collections ----------
Flight::route('GET    /api/@db/collections',                        [CollectionController::class, 'index']);
Flight::route('POST   /api/@db/collections',                        [CollectionController::class, 'store']);
Flight::route('DELETE /api/@db/collections/@collection',            [CollectionController::class, 'destroy']);
Flight::route('POST   /api/@db/collections/@collection/rename',     [CollectionController::class, 'rename']);

// ---------- Documents (CRUD) ----------
Flight::route('GET    /api/@db/@collection/documents',             [DocumentController::class, 'index']);
Flight::route('POST   /api/@db/@collection/documents',             [DocumentController::class, 'store']);
Flight::route('GET    /api/@db/@collection/documents/@id',         [DocumentController::class, 'show']);
Flight::route('PUT    /api/@db/@collection/documents/@id',         [DocumentController::class, 'update']);
Flight::route('DELETE /api/@db/@collection/documents',             [DocumentController::class, 'destroy']);
Flight::route('POST   /api/@db/@collection/save',                   [DocumentController::class, 'save']);
Flight::route('POST   /api/@db/@collection/count',                 [DocumentController::class, 'count']);

// ---------- Query Operators ----------
Flight::route('POST /api/@db/@collection/query',                    [DocumentController::class, 'query']);

// ---------- Encryption ----------
Flight::route('POST /api/@db/@collection/encryption',               [EncryptionController::class, 'store']);

// ---------- Schema ----------
Flight::route('GET  /api/@db/@collection/schema',                   [SchemaController::class, 'show']);
Flight::route('POST /api/@db/@collection/schema',                   [SchemaController::class, 'store']);
Flight::route('POST /api/@db/@collection/validate',                 [SchemaController::class, 'validate']);

// ---------- ACL ----------
Flight::route('GET  /api/@db/@collection/acl',                      [AclController::class, 'show']);
Flight::route('PUT  /api/@db/@collection/acl',                      [AclController::class, 'store']);
Flight::route('POST /api/@db/@collection/acl/test',                 [AclController::class, 'test']);

// ---------- Audit ----------
Flight::route('GET  /api/audit/logs',                               [AuditController::class, 'index']);
Flight::route('GET  /api/audit/stats',                              [AuditController::class, 'stats']);

// ---------- Soft Deletes ----------
Flight::route('POST /api/@db/@collection/soft-deletes/toggle',       [SoftDeleteController::class, 'toggle']);
Flight::route('POST /api/@db/@collection/restore',                  [SoftDeleteController::class, 'restore']);
Flight::route('POST /api/@db/@collection/force-delete',             [SoftDeleteController::class, 'forceDelete']);

// ---------- Hooks ----------
Flight::route('GET  /api/@db/@collection/hooks',                    [HookController::class, 'index']);

// ---------- Populate / Relations ----------
Flight::route('POST /api/@db/@collection/populate',                 [RelationController::class, 'populate']);

// ---------- Indexes ----------
Flight::route('GET    /api/@db/indexes',                            [IndexController::class, 'index']);
Flight::route('POST   /api/@db/@collection/indexes',               [IndexController::class, 'store']);
Flight::route('DELETE /api/@db/indexes/@name',                      [IndexController::class, 'destroy']);

// ---------- Health & Monitoring ----------
Flight::route('GET  /api/@db/health',                               [HealthController::class, 'show']);
Flight::route('POST /api/@db/vacuum',                               [HealthController::class, 'vacuum']);
Flight::route('GET  /api/@db/metrics',                              [HealthController::class, 'metrics']);

// ---------- ID Modes & Config ----------
Flight::route('GET  /api/@db/@collection/config',                   [ConfigController::class, 'show']);
Flight::route('POST /api/@db/@collection/id-mode',                  [ConfigController::class, 'idMode']);
Flight::route('POST /api/@db/@collection/config/save',              [ConfigController::class, 'save']);

// ---------- Transaction ----------
Flight::route('POST /api/@db/transaction',                          [ConfigController::class, 'transaction']);

// ---------- Setup Wizard ----------
Flight::route('GET  /api/setup/status',                             [SetupController::class, 'status']);
Flight::route('POST /api/setup/initialize',                         [SetupController::class, 'initialize']);

// ---------- Status ----------
Flight::route('GET /api/status',                                    [StatusController::class, 'index']);

// ---------- SSOT BC shim (deprecated) ----------
Flight::route('GET  /api/@db/@collection/ssot',                    [StatusController::class, 'ssotGet']);
Flight::route('PUT  /api/@db/@collection/ssot',                    [StatusController::class, 'ssotPut']);
Flight::route('POST /api/@db/@collection/ssot/@any',               [StatusController::class, 'ssotAny']);
Flight::route('GET  /api/ssot/@any',                               [StatusController::class, 'ssotRoot']);