<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\SchemaMapper;
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
                $adminExists = $u->findOne(['$or'=>[
                    ['role'=>['$in'=>['superadmin','admin']]],
                    ['roles' => ['$in' => ['superadmin', 'admin']]]
                ]]) !== null;
            } catch (Throwable $e) {}
        }

        Flight::json([
            'needs_setup'  => !$adminExists,
            'has_auth_db'  => $hasAuth,
            'user_count'   => $userCount,
            'admin_exists' => $adminExists,
            'version'      => '2.1.0-permissions',
            'acl_model'    => 'user -> role -> resource(db.collection) -> action + field_level + row_level',
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
            $permCol->useSoftDeletes(true);
            SchemaMapper::applyAll($permCol, $this->getPermissionSSOT());

            foreach ($this->getDefaultPermissions() as $p) {
                try { $permCol->insert($p); } catch (Throwable $e) {}
            }

            // 2. roles (permissions = relasi ke koleksi permissions)
            if (!$client->collectionExists('auth', 'roles')) {
                $client->createCollection('auth', 'roles');
            }
            $roleCol = $client->selectCollection('auth', 'roles');
            $roleCol->setIdModePrefix('role_');
            $roleCol->useSoftDeletes(true);
            SchemaMapper::applyAll($roleCol, $this->getRoleSSOT());
            // Override: permissions menyimpan array, bukan string
            $nativeSchema = $roleCol->getSchema();
            $nativeSchema['permissions']['type'] = 'array';
            $roleCol->setSchema($nativeSchema);
            $roleCol->saveConfiguration();

            foreach ($this->getDefaultRoles() as $r) {
                try { $roleCol->insert($r); } catch (Throwable $e) {}
            }

            // 3. users (roles = relasi ke koleksi roles, encryption + searchable)
            if (!$client->collectionExists('auth', 'users')) {
                $client->createCollection('auth', 'users');
            }
            $userCol = $client->selectCollection('auth', 'users');
            $userCol->setIdModePrefix('usr_');
            $userCol->useSoftDeletes(true);

            if (!empty($_ENV['ENCRYPTION_KEY'])) {
                $userCol->setEncryptionKey($_ENV['ENCRYPTION_KEY']);
            }
            SchemaMapper::applyAll($userCol, $this->getUserSSOT(), $_ENV['ENCRYPTION_KEY'] ?? null);
            // Override: roles menyimpan array, bukan string
            $nativeSchema = $userCol->getSchema();
            $nativeSchema['roles']['type'] = 'array';
            $userCol->setSchema($nativeSchema);
            $userCol->saveConfiguration();

            // Buat superadmin – SINGLE role relation
            if (!$userCol->findOne(['username' => $adminUser])) {
                $userCol->insert([
                    '_id'                  => 'usr_superadmin',
                    'username'             => $adminUser,
                    'email'                => $adminEmail,
                    'name'                 => 'Super Administrator',
                    'password_hash'        => password_hash($adminPass, PASSWORD_ARGON2ID),
                    'role'                 => 'superadmin', // SINGLE relation
                    'roles'                => ['superadmin'], // BC
                    'active'               => true,
                    'must_change_password' => true,
                    'created_at'           => date('c'),
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

    private function getPermissionSSOT(): array
    {
        return [
            'name'        => ['type' => 'string', 'required' => true, 'unique' => true, 'label' => 'Name', 'filterable' => true, 'regex'=>'/^[a-z0-9_.\:\*\-]+$/'],
            'label'       => ['type' => 'string', 'label' => 'Label'],
            'group'       => ['type' => 'string', 'label' => 'Group', 'default'=>'custom','filterable'=>true],
            'description' => ['type' => 'text', 'label' => 'Description'],
            'is_system'   => ['type' => 'bool', 'label' => 'System', 'default'=>false,'readonly'=>true],
            'created_at'  => ['type' => 'datetime', 'label' => 'Created', 'readonly'=>true],
        ];
    }

    /**
     * SSOT schema untuk roles — permissions adalah relasi many-to-many ke koleksi permissions
     */
    private function getRoleSSOT(): array
    {
        return [
            'name'        => ['type' => 'string', 'required' => true, 'unique' => true, 'label' => 'Name', 'filterable' => true],
            'label'       => ['type' => 'string', 'label' => 'Label'],
            'permissions' => [
                'type'     => 'relation',
                'label'    => 'Permissions',
                'multiple' => true,
                'relation' => [
                    'db'         => 'auth',
                    'collection' => 'permissions',
                    'field'      => 'name',
                    'display'    => 'label',
                ],
            ],
            'is_system' => ['type' => 'bool', 'default' => false, 'label' => 'System Role'],
        ];
    }

    /**
     * SSOT schema untuk users — role SINGLE relation ke auth.roles
     * user.role → auth.roles._id (SINGLE, required)
     * roles[] tetap disimpan untuk BC JWT
     */
    private function getUserSSOT(): array
    {
        return [
            'username'      => ['type' => 'string', 'required' => true, 'unique' => true, 'label' => 'Username', 'filterable' => true, 'searchable' => true],
            'email'         => ['type' => 'email', 'required' => true, 'unique' => true, 'label' => 'Email', 'filterable' => true, 'searchable' => true],
            'name'          => ['type' => 'string', 'label' => 'Full Name'],
            'password_hash' => ['type' => 'password', 'required' => true, 'label' => 'Password', 'hidden' => true],
            // SINGLE role relation – utama
            'role'          => [
                'type'     => 'relation',
                'label'    => 'Role',
                'required' => true,
                'multiple' => false,
                'default'  => 'user',
                'relation' => [
                    'db'         => 'auth',
                    'collection' => 'roles',
                    'field'      => '_id',
                    'display'    => 'name',
                    'type'       => 'one',
                ],
            ],
            // BC: roles array (hidden)
            'roles'         => [
                'type'     => 'array',
                'label'    => 'Roles (legacy)',
                'hidden'   => true,
            ],
            'active'               => ['type' => 'bool', 'default' => true, 'label' => 'Active'],
            'must_change_password' => ['type' => 'bool', 'default' => false, 'label' => 'Must Change Password'],
            'created_at'           => ['type' => 'datetime', 'label' => 'Created At', 'readonly' => true],
        ];
    }

    private function getDefaultPermissions(): array
    {
        return [
            // CRUD
            ['_id'=>'read','name' => 'read', 'label' => 'Read', 'group'=>'crud','description'=>'Find, findOne, count','is_system'=>true,'created_at'=>date('c')],
            ['_id'=>'find','name' => 'find', 'label' => 'Find', 'group'=>'crud','is_system'=>true,'created_at'=>date('c')],
            ['_id'=>'count','name' => 'count', 'label' => 'Count', 'group'=>'crud','is_system'=>true,'created_at'=>date('c')],
            ['_id'=>'create','name' => 'create', 'label' => 'Create', 'group'=>'crud','is_system'=>true,'created_at'=>date('c')],
            ['_id'=>'insert','name' => 'insert', 'label' => 'Insert', 'group'=>'crud','is_system'=>true,'created_at'=>date('c')],
            ['_id'=>'update','name' => 'update', 'label' => 'Update', 'group'=>'crud','is_system'=>true,'created_at'=>date('c')],
            ['_id'=>'delete','name' => 'delete', 'label' => 'Delete', 'group'=>'crud','is_system'=>true,'created_at'=>date('c')],
            ['_id'=>'remove','name' => 'remove', 'label' => 'Remove', 'group'=>'crud','is_system'=>true,'created_at'=>date('c')],
            // admin
            ['_id'=>'manage_schema','name' => 'manage_schema', 'label' => 'Manage Schema','group'=>'admin','is_system'=>true,'created_at'=>date('c')],
            ['_id'=>'manage_acl','name' => 'manage_acl', 'label' => 'Manage ACL','group'=>'admin','is_system'=>true,'created_at'=>date('c')],
            ['_id'=>'manage_index','name'=>'manage_index','label'=>'Manage Indexes','group'=>'admin','is_system'=>true,'created_at'=>date('c')],
            ['_id'=>'manage_hooks','name'=>'manage_hooks','label'=>'Manage Hooks','group'=>'admin','is_system'=>true,'created_at'=>date('c')],
            // data ops
            ['_id'=>'export','name' => 'export', 'label' => 'Export Data','group'=>'data','description'=>'Export JSON/CSV','is_system'=>false,'created_at'=>date('c')],
            ['_id'=>'import','name' => 'import', 'label' => 'Import Data','group'=>'data','is_system'=>false,'created_at'=>date('c')],
            ['_id'=>'publish','name'=>'publish','label'=>'Publish','group'=>'workflow','is_system'=>false,'created_at'=>date('c')],
            ['_id'=>'approve','name'=>'approve','label'=>'Approve','group'=>'workflow','is_system'=>false,'created_at'=>date('c')],
            ['_id'=>'archive','name'=>'archive','label'=>'Archive','group'=>'workflow','is_system'=>false,'created_at'=>date('c')],
            ['_id'=>'restore','name'=>'restore','label'=>'Restore','group'=>'data','is_system'=>false,'created_at'=>date('c')],
            ['_id'=>'force_delete','name'=>'force_delete','label'=>'Force Delete','group'=>'admin','is_system'=>false,'created_at'=>date('c')],
            ['_id'=>'*','name'=>'*','label'=>'Full Access (*)','group'=>'system','is_system'=>true,'created_at'=>date('c')],
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