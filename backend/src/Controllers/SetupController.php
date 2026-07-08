<?php
declare(strict_types=1);

namespace App\Controllers;

use BangronDB\Client;
use Throwable;

class SetupController
{
    private static function dbPath(): string
    {
        return $_ENV['DB_PATH'] ?? dirname(__DIR__, 3) . '/storage/data';
    }

    /**
     * GET /api/setup/status
     *
     * Returns whether the initial setup (admin user, auth DB) has been completed.
     */
    public function status(): void
    {
        $dbPath    = self::dbPath();
        $client    = new Client($dbPath);
        $hasAuth   = $client->dbExists('auth') && $client->collectionExists('auth', 'users');
        $adminExists = false;
        $userCount   = 0;

        if ($hasAuth) {
            try {
                $u         = $client->selectCollection('auth', 'users');
                $userCount = $u->count();
                $adminExists = $u->findOne(['roles' => ['$in' => ['superadmin', 'admin']]]) !== null
                    || $u->findOne(['username' => 'superadmin']) !== null;
            } catch (Throwable $e) {
                // Silently continue — auth DB may be partially initialized
            }
        }

        \Flight::json([
            'needs_setup' => !$adminExists,
            'has_auth_db' => $hasAuth,
            'user_count'  => $userCount,
            'admin_exists' => $adminExists,
            'version'     => '1.3.0-enhanced-schema',
            'acl_model'   => 'user -> role -> resource(db.collection) -> action + field_level + row_level',
        ]);
    }

    /**
     * POST /api/setup/initialize
     *
     * Seeds the auth database with roles, creates the admin user,
     * and initializes the application database with default collections.
     */
    public function initialize(): void
    {
        $body       = \Flight::request()->data->getData();
        $adminUser  = trim($body['username'] ?? 'superadmin');
        $adminEmail = trim($body['email'] ?? 'superadmin@bangrondb.local');
        $adminPass  = $body['password'] ?? 'SuperAdmin123!';
        $appDb      = $body['app_db'] ?? 'app';

        $dbPath = self::dbPath();
        $client = new Client($dbPath);

        // --- Auth database + roles ---
        if (!$client->dbExists('auth')) {
            $client->createDB('auth');
        }
        if (!$client->collectionExists('auth', 'roles')) {
            $client->createCollection('auth', 'roles');
        }
        if (!$client->collectionExists('auth', 'users')) {
            $client->createCollection('auth', 'users');
        }

        $roles     = $client->selectCollection('auth', 'roles');
        $seedRoles = [
            ['_id' => 'superadmin', 'name' => 'superadmin', 'label' => 'Super Administrator', 'permissions' => ['*'], 'is_system' => true],
            ['_id' => 'admin',      'name' => 'admin',      'label' => 'Administrator',         'permissions' => ['read', 'create', 'update', 'delete', 'manage_schema', 'manage_acl'], 'is_system' => true],
            ['_id' => 'editor',     'name' => 'editor',     'label' => 'Editor',                'permissions' => ['read', 'create', 'update'], 'is_system' => true],
            ['_id' => 'user',       'name' => 'user',       'label' => 'User',                  'permissions' => ['read'], 'is_system' => true],
            ['_id' => 'guest',      'name' => 'guest',      'label' => 'Guest',                 'permissions' => [], 'is_system' => true],
        ];

        foreach ($seedRoles as $r) {
            try {
                $roles->save($r);
            } catch (Throwable $e) {
                // Role may already exist — silently continue
            }
        }

        // --- Admin user ---
        $users    = $client->selectCollection('auth', 'users');
        $existing = $users->findOne(['$or' => [['username' => $adminUser], ['email' => $adminEmail]]]);

        if (!$existing) {
            $users->insert([
                'username'      => $adminUser,
                'email'         => $adminEmail,
                'name'          => 'Super Admin',
                'password_hash' => password_hash($adminPass, PASSWORD_ARGON2ID),
                'roles'         => ['superadmin'],
                'active'        => true,
                'created_at'    => date('c'),
            ]);
        }

        // --- Application database with default collections + ACL ---
        if (!$client->dbExists($appDb)) {
            $client->createDB($appDb);
        }

        $defaultCollections = ['users', 'posts', 'tasks'];

        foreach ($defaultCollections as $cName) {
            if (!$client->collectionExists($appDb, $cName)) {
                $client->createCollection($appDb, $cName);
            }

            $col = $client->selectCollection($appDb, $cName);

            if (method_exists($col, 'setCustomConfig')) {
                $col->setCustomConfig('acl', [
                    'enabled'     => true,
                    'default_role' => 'guest',
                    'roles'       => [
                        'superadmin' => ['*'],
                        'admin'      => ['read', 'create', 'update', 'delete', 'manage_schema'],
                        'editor'     => ['read', 'create', 'update'],
                        'user'       => ['read'],
                        'guest'      => [],
                    ],
                    'field_rules' => [],
                    'row_filters' => [],
                    'api_keys'    => [],
                ]);
                $col->saveConfiguration();
            }
        }

        \Flight::json([
            'ok'     => true,
            'message' => 'Setup berhasil diselesaikan',
            'superadmin' => [
                'username'  => $adminUser,
                'email'     => $adminEmail,
                'password'  => !empty($body['password']) ? '***set***' : 'SuperAdmin123!',
                'login_url' => '/api/auth/login',
            ],
            'databases_created' => ['auth', $appDb],
            'next' => 'POST /api/auth/login {username, password} untuk masuk ke sistem',
        ]);
    }
}