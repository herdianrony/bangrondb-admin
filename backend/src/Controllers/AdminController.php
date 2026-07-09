<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Security\Acl;
use App\Security\Audit;
use App\Security\RefreshTokenStore;
use BangronDB\Client;
use Throwable;

class AdminController
{
    private function client(): Client
    {
        return \Flight::bangron()->getClient();
    }

    private function ensureAuthDb(Client $client): void
    {
        if (!$client->dbExists('auth')) {
            $client->createDB('auth');
        }
        if (!$client->collectionExists('auth', 'users')) {
            $client->createCollection('auth', 'users');
        }
        // ensure users schema single role
        try {
            $col = $client->selectCollection('auth', 'users');
            if (method_exists($col, 'setSchema')) {
                $col->setSchema([
                    'username' => ['required'=>true,'type'=>'string','unique'=>true],
                    'email' => ['type'=>'email','unique'=>true],
                    'name' => ['type'=>'string'],
                    'password_hash' => ['type'=>'string','hidden'=>true],
                    'role' => [
                        'required'=>true,'type'=>'relation',
                        'relation'=>['db'=>'auth','collection'=>'roles','field'=>'_id','display'=>'name','type'=>'one'],
                        'default'=>'user'
                    ],
                    'roles' => ['type'=>'array','hidden'=>true],
                    'active' => ['type'=>'bool','default'=>true],
                    'created_at' => ['type'=>'datetime','readonly'=>true],
                ]);
                $col->saveConfiguration();
            }
        } catch (\Throwable $e) {}
    }

    private function ensureRolesCollection(Client $client): void
    {
        if (!$client->dbExists('auth')) {
            $client->createDB('auth');
        }
        if (!$client->collectionExists('auth', 'roles')) {
            $client->createCollection('auth', 'roles');
        }
    }

    // ------------------------------------------------------------------
    // Users
    // ------------------------------------------------------------------

    /**
     * GET /api/admin/users — List all auth users (password_hash excluded).
     */
    public function users(): void
    {
        $client = $this->client();

        if (!$client->dbExists('auth') || !$client->collectionExists('auth', 'users')) {
            \Flight::json(['data' => [], 'count' => 0]);
            return;
        }

        $col = $client->selectCollection('auth', 'users');
        $users = $col->find([], ['password_hash' => 0], ['created_at' => -1], 200);

        foreach ($users as &$user) {
            unset($user['password_hash']);
        }

        \Flight::json(['data' => $users, 'count' => count($users)]);
    }

    /**
     * POST /api/admin/users — Create a new auth user.
     */
    public function createUser(): void
    {
        $body = \Flight::request()->data->getData();
        $client = $this->client();

        $this->ensureAuthDb($client);
        $col = $client->selectCollection('auth', 'users');

        $username = trim($body['username'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? bin2hex(random_bytes(6));
        $role = $body['role'] ?? $body['roles'][0] ?? 'user';
        $roles = $body['roles'] ?? [$role];
        if (is_string($roles)) $roles = [$roles];

        if (!$username) {
            \Flight::json(['error' => true, 'message' => 'Username wajib diisi'], 400);
            return;
        }

        if ($col->findOne(['username' => $username])) {
            \Flight::json(['error' => true, 'message' => 'Username sudah digunakan'], 409);
            return;
        }

        $doc = [
            'username' => $username,
            'email' => $email,
            'name' => $body['name'] ?? $username,
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'role' => $role,
            'roles' => is_array($roles) ? $roles : [$role],
            'active' => $body['active'] ?? true,
            'created_at' => date('c'),
        ];

        $id = $col->insert($doc);

        Audit::log(
            \Flight::bangron()->getPath(),
            'admin.user.create',
            'auth',
            'users',
            ['admin' => 'api'],
            ['new_user' => $username, 'roles' => $roles]
        );

        \Flight::json([
            'ok' => true,
            '_id' => $id,
            'generated_password' => $body['password'] ? null : $password,
        ], 201);
    }

    /**
     * PUT /api/admin/users/@id — Update an existing user.
     */
    public function updateUser(string $id): void
    {
        $body = \Flight::request()->data->getData();
        $client = $this->client();
        $col = $client->selectCollection('auth', 'users');

        $user = $col->findOne(['_id' => $id]);
        if (!$user) {
            \Flight::json(['error' => true, 'message' => 'Data tidak ditemukan'], 404);
            return;
        }

        foreach (['email', 'name', 'active'] as $field) {
            if (array_key_exists($field, $body)) {
                $user[$field] = $body[$field];
            }
        }
        if (isset($body['role'])) {
            $user['role'] = $body['role'];
            $user['roles'] = [$body['role']];
        }
        if (array_key_exists('roles', $body)) {
            $r = $body['roles'];
            if (is_string($r)) $r = [$r];
            $user['roles'] = $r;
            if (empty($user['role']) && !empty($r[0])) $user['role'] = $r[0];
        }

        if (!empty($body['password'])) {
            $user['password_hash'] = password_hash($body['password'], PASSWORD_ARGON2ID);
        }

        $col->save($user);
        \App\Security\Acl::clearCache();
        \Flight::json(['ok' => true]);
    }

    /**
     * DELETE /api/admin/users/@id — Delete a user and revoke their refresh tokens.
     */
    public function deleteUser(string $id): void
    {
        $client = $this->client();
        $col = $client->selectCollection('auth', 'users');

        $n = $col->remove(['_id' => $id]);

        RefreshTokenStore::revokeUser($client, $id);

        \Flight::json(['ok' => true, 'deleted' => $n]);
    }

    /**
     * POST /api/admin/users/@id/reset-password — Reset a user's password.
     */
    public function resetPassword(string $id): void
    {
        $body = \Flight::request()->data->getData();
        $newPass = $body['password'] ?? bin2hex(random_bytes(8));
        $client = $this->client();
        $col = $client->selectCollection('auth', 'users');

        $user = $col->findOne(['_id' => $id]);
        if (!$user) {
            \Flight::json(['error' => true, 'message' => 'Data tidak ditemukan'], 404);
            return;
        }

        $user['password_hash'] = password_hash($newPass, PASSWORD_ARGON2ID);
        $user['must_change_password'] = true;
        $col->save($user);

        RefreshTokenStore::revokeUser($client, $id);

        \Flight::json(['ok' => true, 'new_password' => $newPass]);
    }

    /**
     * POST /api/admin/users/@id/toggle-active — Toggle a user's active status.
     */
    public function toggleActive(string $id): void
    {
        $client = $this->client();
        $col = $client->selectCollection('auth', 'users');

        $user = $col->findOne(['_id' => $id]);
        if (!$user) {
            \Flight::json(['error' => true, 'message' => 'Data tidak ditemukan'], 404);
            return;
        }

        $user['active'] = !($user['active'] ?? true);
        $col->save($user);

        if (!$user['active']) {
            RefreshTokenStore::revokeUser($client, $id);
        }

        \Flight::json(['ok' => true, 'active' => $user['active']]);
    }

    // ------------------------------------------------------------------
    // Roles
    // ------------------------------------------------------------------

    /**
     * GET /api/admin/roles — List all roles.
     */
    public function roles(): void
    {
        $client = $this->client();

        if (!$client->dbExists('auth') || !$client->collectionExists('auth', 'roles')) {
            \Flight::json(['data' => [], 'count' => 0]);
            return;
        }

        $col = $client->selectCollection('auth', 'roles');
        $roles = $col->find([], null, ['name' => 1], 200);

        \Flight::json(['data' => $roles, 'count' => count($roles)]);
    }

    /**
     * POST /api/admin/roles — Create a new role.
     */
    public function createRole(): void
    {
        $body = \Flight::request()->data->getData();
        $client = $this->client();

        $this->ensureRolesCollection($client);
        $col = $client->selectCollection('auth', 'roles');

        $name = trim($body['name'] ?? '');
        if (!$name) {
            \Flight::json(['error' => true, 'message' => 'Nama role wajib diisi'], 400);
            return;
        }

        $doc = [
            '_id' => $name,
            'name' => $name,
            'label' => $body['label'] ?? ucfirst($name),
            'permissions' => $body['permissions'] ?? ['read'],
            'description' => $body['description'] ?? '',
            'is_system' => false,
            'created_at' => date('c'),
        ];

        $col->save($doc);

        \Flight::json(['ok' => true, 'role' => $doc], 201);
    }

    /**
     * PUT /api/admin/roles/@name — Update an existing role.
     */
    public function updateRole(string $name): void
    {
        $body = \Flight::request()->data->getData();
        $client = $this->client();
        $col = $client->selectCollection('auth', 'roles');

        $role = $col->findOne(['name' => $name]) ?? $col->findOne(['_id' => $name]);
        if (!$role) {
            \Flight::json(['error' => true, 'message' => 'Data tidak ditemukan'], 404);
            return;
        }

        foreach (['label', 'permissions', 'description'] as $field) {
            if (isset($body[$field])) {
                $role[$field] = $body[$field];
            }
        }

        $col->save($role);

        Acl::clearCache();

        \Flight::json(['ok' => true]);
    }

    /**
     * DELETE /api/admin/roles/@name — Delete a non-system role.
     */
    public function deleteRole(string $name): void
    {
        $client = $this->client();
        $col = $client->selectCollection('auth', 'roles');

        $role = $col->findOne(['name' => $name]) ?? $col->findOne(['_id' => $name]);
        if ($role && ($role['is_system'] ?? false)) {
            \Flight::json(['error' => true, 'message' => 'Tidak dapat menghapus role sistem'], 403);
            return;
        }

        $n = $col->remove(['$or' => [['name' => $name], ['_id' => $name]]]);

        Acl::clearCache();

        \Flight::json(['ok' => true, 'deleted' => $n]);
    }

    // ------------------------------------------------------------------
    // ACL Check (permission matrix test helper)
    // ------------------------------------------------------------------

    /**
     * POST /api/admin/acl/check — Test ACL permission for given roles/action on a collection.
     */
    public function aclCheck(): void
    {
        $body = \Flight::request()->data->getData();
        $db = $body['db'] ?? 'app';
        $collection = $body['collection'] ?? 'users';
        $roles = (array) ($body['roles'] ?? ['guest']);
        $action = $body['action'] ?? 'read';

        try {
            $col = \Flight::bangron()->getCollection($db, $collection);
            $acl = Acl::loadCached($col, $db, $collection);
            $allowed = Acl::can($roles, $action, $acl);

            \Flight::json([
                'allowed' => $allowed,
                'roles' => $roles,
                'action' => $action,
                'db' => $db,
                'collection' => $collection,
                'acl_enabled' => $acl['enabled'] ?? false,
            ]);
        } catch (Throwable $e) {
            \Flight::json(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // Token management
    // ------------------------------------------------------------------

    /**
     * POST /api/admin/users/@id/revoke-tokens — Revoke all refresh tokens for a user.
     */
    public function revokeTokens(string $id): void
    {
        $client = $this->client();
        $n = RefreshTokenStore::revokeUser($client, $id);

        \Flight::json([
            'ok' => true,
            'revoked_refresh' => $n,
            'note' => 'Access token akan kadaluarsa sesuai waktu, atau bisa dicabut manual melalui jti',
        ]);
    }
}