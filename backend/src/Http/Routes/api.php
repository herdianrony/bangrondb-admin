<?php
declare(strict_types=1);

/**
 * BangronDB Admin API Routes - Clean & Organized
 * 
 * Best Practice FlightPHP + BangronDB Pattern
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
// DATABASE ROUTES (Group)
// ═══════════════════════════════════════════════════════════════
Flight::group('/databases', function () {
    
    // Database CRUD
    Flight::route('GET  /', [DatabaseController::class, 'index']);
    Flight::route('POST /', [DatabaseController::class, 'store']);
    
    Flight::group('/@db', function () {
        Flight::route('DELETE', [DatabaseController::class, 'destroy']);
        Flight::route('POST /rename', [DatabaseController::class, 'rename']);
        
        // Database utilities
        Flight::route('GET /health', [HealthController::class, 'show']);
        Flight::route('GET /metrics', [HealthController::class, 'metrics']);
        Flight::route('POST /vacuum', [HealthController::class, 'vacuum']);
        Flight::route('POST /transaction', [ConfigController::class, 'transaction']);
        
        // Indexes at DB level
        Flight::route('GET /indexes', [IndexController::class, 'index']);
        Flight::route('DELETE /indexes/@name', [IndexController::class, 'destroy']);
        
        // ═══════════════════════════════════════════════════════════════
        // COLLECTION ROUTES (Nested under database)
        // ═══════════════════════════════════════════════════════════════
        Flight::group('/collections', function () {
            
            Flight::route('GET  /', [CollectionController::class, 'index']);
            Flight::route('POST /', [CollectionController::class, 'store']);
            
            Flight::group('/@col', function () {
                Flight::route('DELETE', [CollectionController::class, 'destroy']);
                Flight::route('POST /rename', [CollectionController::class, 'rename']);
                
                // Documents
                Flight::route('GET    /documents', [DocumentController::class, 'index']);
                Flight::route('POST   /documents', [DocumentController::class, 'store']);
                Flight::route('GET    /documents/@id', [DocumentController::class, 'show']);
                Flight::route('PUT    /documents/@id', [DocumentController::class, 'update']);
                Flight::route('DELETE /documents', [DocumentController::class, 'destroy']);
                Flight::route('POST   /save', [DocumentController::class, 'save']);
                Flight::route('POST   /count', [DocumentController::class, 'count']);
                Flight::route('POST   /query', [DocumentController::class, 'query']);
                
                // Schema
                Flight::route('GET  /schema', [SchemaController::class, 'show']);
                Flight::route('POST /schema', [SchemaController::class, 'store']);
                Flight::route('POST /schema/validate', [SchemaController::class, 'validate']);
                
                // ACL
                Flight::route('GET  /acl', [AclController::class, 'show']);
                Flight::route('PUT  /acl', [AclController::class, 'store']);
                Flight::route('POST /acl/test', [AclController::class, 'test']);
                
                // Config
                Flight::route('GET  /config', [ConfigController::class, 'show']);
                Flight::route('POST /config/save', [ConfigController::class, 'save']);
                Flight::route('POST /id-mode', [ConfigController::class, 'idMode']);
                
                // Encryption
                Flight::route('POST /encryption', [EncryptionController::class, 'store']);
                
                // Soft Deletes
                Flight::route('POST /soft-deletes', [SoftDeleteController::class, 'toggle']);
                Flight::route('POST /restore', [SoftDeleteController::class, 'restore']);
                Flight::route('POST /force-delete', [SoftDeleteController::class, 'forceDelete']);
                
                // Hooks
                Flight::route('GET /hooks', [HookController::class, 'index']);
                
                // Relations / Populate
                Flight::route('POST /populate', [RelationController::class, 'populate']);
                
                // Indexes
                Flight::route('POST /indexes', [IndexController::class, 'store']);
            });
        });
    });
});

// ═══════════════════════════════════════════════════════════════
// SETUP
// ═══════════════════════════════════════════════════════════════
Flight::route('GET  /setup/status', [SetupController::class, 'status']);
Flight::route('POST /setup/initialize', [SetupController::class, 'initialize']);

// ═══════════════════════════════════════════════════════════════
// AUDIT
// ═══════════════════════════════════════════════════════════════
Flight::route('GET /audit/logs', [AuditController::class, 'index']);
Flight::route('GET /audit/stats', [AuditController::class, 'stats']);

// ═══════════════════════════════════════════════════════════════
// STATUS
// ═══════════════════════════════════════════════════════════════
Flight::route('GET /status', [StatusController::class, 'index']);

// ═══════════════════════════════════════════════════════════════
// BACKWARD COMPATIBILITY (deprecated)
// ═══════════════════════════════════════════════════════════════
Flight::route('GET  /ssot/@db/@col', [StatusController::class, 'ssotGet']);
Flight::route('PUT  /ssot/@db/@col', [StatusController::class, 'ssotPut']);
Flight::route('POST /ssot/@db/@col/@any', [StatusController::class, 'ssotAny']);
Flight::route('GET  /ssot/@any', [StatusController::class, 'ssotRoot']);