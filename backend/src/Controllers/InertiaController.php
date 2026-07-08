<?php
declare(strict_types=1);

namespace App\Controllers;

class InertiaController
{
    /**
     * GET /
     * Renders Dashboard via Inertia (or Setup if not initialized).
     */
    public function index(): void
    {
        $dbPath = defined('BANGRON_DB_PATH') ? BANGRON_DB_PATH : dirname(__DIR__, 2) . '/storage/data';

        if ($this->needsSetup($dbPath)) {
            \Flight::inertia()->render('Setup/Index', [
                'stats' => ['databases' => 0, 'collections' => 0, 'documents' => 0, 'total_size_mb' => 0, 'php_version' => PHP_VERSION, 'health' => ['status' => 'ok']],
            ]);
            return;
        }

        \Flight::inertia()->render('Dashboard/Index', [
            'stats' => \Flight::bangron()->dashboardStats()
        ]);
    }

    /**
     * GET /@path — SPA catch-all for all Vue pages.
     *
     * Maps clean URLs to Inertia page components:
     *   /collections    → Collections/Index
     *   /documents      → Documents/Index
     *   /databases      → Databases/Index
     *   /users          → Users/Index
     *   /roles          → Roles/Index
     *   /tokens         → Tokens/Index
     *   /acl            → Acl/Index
     *   /schema         → Schema/Index
     *   /query          → Query/Index
     *   /config         → Config/Index
     *   /health         → Health/Index
     *   /encryption     → Encryption/Index
     *   /indexes        → Indexes/Index
     *   /soft-deletes   → SoftDeletes/Index
     *   /hooks          → Hooks/Index
     *   /relations      → Relations/Index
     *   /setup          → Setup/Index
     */
    public function page(string $path): void
    {
        $dbPath = defined('BANGRON_DB_PATH') ? BANGRON_DB_PATH : dirname(__DIR__, 2) . '/storage/data';

        // Guard: redirect to setup if not initialized
        if ($this->needsSetup($dbPath)) {
            header('Location: /');
            exit;
        }

        $map = [
            'collections'  => 'Collections/Index',
            'documents'    => 'Documents/Index',
            'databases'    => 'Databases/Index',
            'users'        => 'Users/Index',
            'roles'        => 'Roles/Index',
            'tokens'       => 'Tokens/Index',
            'acl'          => 'Acl/Index',
            'schema'       => 'Schema/Index',
            'query'        => 'Query/Index',
            'config'       => 'Config/Index',
            'health'       => 'Health/Index',
            'encryption'   => 'Encryption/Index',
            'indexes'      => 'Indexes/Index',
            'soft-deletes' => 'SoftDeletes/Index',
            'hooks'        => 'Hooks/Index',
            'relations'    => 'Relations/Index',
            'setup'        => 'Setup/Index',
        ];

        $segment = explode('/', $path)[0] ?? '';
        $component = $map[$segment] ?? 'Dashboard/Index';

        \Flight::inertia()->render($component, [
            'stats' => \Flight::bangron()->dashboardStats(),
            'path'  => $path
        ]);
    }

    private function needsSetup(string $dbPath): bool
    {
        if (!$dbPath || !is_dir($dbPath)) return true;
        try {
            $client = new \BangronDB\Client($dbPath);
            if (!$client->dbExists('auth') || !$client->collectionExists('auth', 'users')) return true;
            $u = $client->selectCollection('auth', 'users');
            return $u->count() === 0;
        } catch (\Throwable $e) {
            return true;
        }
    }
}