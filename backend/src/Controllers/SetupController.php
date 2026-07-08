<?php
declare(strict_types=1);

namespace App\Controllers;

use Flight;
use Throwable;

class SetupController
{
    /**
     * Ambil BangronService yang sudah terdaftar di Flight
     */
    private function bangron()
    {
        return Flight::bangron();
    }

    public function status(): void
    {
        $bangron = $this->bangron();
        $client = $bangron->getClient();

        $hasAuth = $client->dbExists('auth') &&
                   $client->collectionExists('auth', 'users') &&
                   $client->collectionExists('auth', 'roles') &&
                   $client->collectionExists('auth', 'permissions');

        $adminExists = false;
        $userCount = 0;

        if ($hasAuth) {
            try {
                $u = $client->selectCollection('auth', 'users');
                $userCount = $u->count();
                $adminExists = $u->findOne(['roles' => ['$in' => ['superadmin', 'admin']]]) !== null;
            } catch (Throwable $e) {}
        }

        Flight::json([
            'needs_setup'  => !$adminExists,
            'has_auth_db'  => $hasAuth,
            'user_count'   => $userCount,
            'admin_exists' => $adminExists,
        ]);
    }

    /**
     * POST /setup/initialize
     * Setup awal dengan fitur BangronDB lengkap
     */
    public function initialize(): void
    {
        $body = Flight::request()->data->getData();
        $adminUser  = trim($body['username'] ?? 'admin');
        $adminEmail = trim($body['email'] ?? 'admin@bangron.studio');
        $adminPass  = $body['password'] ?? '';
        $appDb      = trim($body['app_db'] ?? 'app');

        if (strlen($adminUser) < 3) {
            Flight::json(['ok' => false, 'message' => 'Username minimal 3 karakter'], 400);
            return;
        }
        if (strlen($adminPass) < 8) {
            Flight::json(['ok' => false, 'message' => 'Password minimal 8 karakter'], 400);
            return;
        }

        $bangron = $this->bangron();
        $client = $bangron->getClient();

        try {
            // ═══════════════════════════════════════════════════════════════
            // DATABASE: auth
            // ═══════════════════════════════════════════════════════════════
            if (!$client->dbExists('auth')) {
                $client->createDB('auth');
            }

            // 1. permissions
            if (!$client->collectionExists('auth', 'permissions')) {
                $client->createCollection('auth', 'permissions');
            }
            $permCol = $client->selectCollection('auth', 'permissions');
            $permCol->setIdModePrefix('perm_');
            $permCol->setSchema($this->getPermissionSchema());
            $permCol->useSoftDeletes(true);
            $permCol->saveConfiguration();

            foreach ($this->getDefaultPermissions() as $p) {
                try { $permCol->insert($p); } catch (Throwable $e) {}
            }

            // 2. roles
            if (!$client->collectionExists('auth', 'roles')) {
                $client->createCollection('auth', 'roles');
            }
            $roleCol = $client->selectCollection('auth', 'roles');
            $roleCol->setIdModePrefix('role_');
            $roleCol->setSchema($this->getRoleSchema());
            $roleCol->useSoftDeletes(true);
            $roleCol->saveConfiguration();

            foreach ($this->getDefaultRoles() as $r) {
                try { $roleCol->insert($r); } catch (Throwable $e) {}
            }

            // 3. users (encryption + searchable)
            if (!$client->collectionExists('auth', 'users')) {
                $client->createCollection('auth', 'users');
            }
            $userCol = $client->selectCollection('auth', 'users');
            $userCol->setIdModePrefix('usr_');
            $userCol->setSchema($this->getUserSchema());
            $userCol->useSoftDeletes(true);

            if (!empty($_ENV['ENCRYPTION_KEY'])) {
                $userCol->setEncryptionKey($_ENV['ENCRYPTION_KEY']);
                $userCol->setSearchableFields(['email', 'username'], true);
            }
            $userCol->saveConfiguration();

            // Buat superadmin
            if (!$userCol->findOne(['username' => $adminUser])) {
                $userCol->insert([
                    'username'      => $adminUser,
                    'email'         => $adminEmail,
                    'name'          => 'Administrator',
                    'password_hash' => password_hash($adminPass, PASSWORD_ARGON2ID),
                    'roles'         => ['superadmin'],
                    'active'        => true,
                    'created_at'    => date('c'),
                ]);
            }

            // ═══════════════════════════════════════════════════════════════
            // DATABASE: app (hanya dibuat, koleksi dibuat nanti via import/export)
            // ═══════════════════════════════════════════════════════════════
            if (!$client->dbExists($appDb)) {
                $client->createDB($appDb);
            }

            Flight::json([
                'ok' => true,
                'message' => 'Setup berhasil! Koleksi auth (users, roles, permissions) sudah siap.',
                'data' => [
                    'auth' => ['users', 'roles', 'permissions'],
                    'app_db' => $appDb,
                    'admin' => $adminUser,
                    'note' => 'Koleksi lain akan dibuat melalui fitur Import/Export'
                ]
            ]);

        } catch (Throwable $e) {
            Flight::json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    private function getPermissionSchema(): array
    {
        return [
            'name' => ['type' => 'string', 'required' => true, 'unique' => true],
            'label' => ['type' => 'string'],
            'description' => ['type' => 'string'],
        ];
    }

    private function getRoleSchema(): array
    {
        return [
            'name' => ['type' => 'string', 'required' => true, 'unique' => true],
            'label' => ['type' => 'string'],
            'permissions' => ['type' => 'array'],
            'is_system' => ['type' => 'bool', 'default' => false],
        ];
    }

    private function getUserSchema(): array
    {
        return [
            'username' => ['type' => 'string', 'required' => true, 'unique' => true],
            'email' => ['type' => 'email', 'required' => true, 'unique' => true],
            'password_hash' => ['type' => 'string', 'required' => true],
            'roles' => ['type' => 'array'],
            'active' => ['type' => 'bool', 'default' => true],
        ];
    }

    private function getDefaultPermissions(): array
    {
        return [
            ['name' => 'read', 'label' => 'Read'],
            ['name' => 'create', 'label' => 'Create'],
            ['name' => 'update', 'label' => 'Update'],
            ['name' => 'delete', 'label' => 'Delete'],
            ['name' => 'manage_schema', 'label' => 'Manage Schema'],
            ['name' => 'manage_acl', 'label' => 'Manage ACL'],
        ];
    }

    private function getDefaultRoles(): array
    {
        return [
            ['name' => 'superadmin', 'label' => 'Super Admin', 'permissions' => ['*'], 'is_system' => true],
            ['name' => 'admin', 'label' => 'Admin', 'permissions' => ['read','create','update','delete','manage_schema','manage_acl']],
            ['name' => 'editor', 'label' => 'Editor', 'permissions' => ['create','read','update']],
            ['name' => 'viewer', 'label' => 'Viewer', 'permissions' => ['read']],
        ];
    }
}