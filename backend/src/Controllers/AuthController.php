<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Middleware\AclMiddleware;
use App\Security\Acl;
use App\Security\Audit;
use App\Security\Jwt;
use App\Security\RefreshTokenStore;
use App\Security\TokenBlacklist;
use App\Logging\LoggerFactory;
use Throwable;

class AuthController
{
    /**
     * POST /api/auth/register
     */
    public function register(): void
    {
        $body     = \Flight::request()->data->getData();
        $username = trim($body['username'] ?? '');
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $role     = $body['role'] ?? 'user';

        if (!$username || !$password) {
            \Flight::json(['error' => true, 'message' => 'Username dan password wajib diisi'], 400);
            return;
        }

        $authDb  = $_ENV['AUTH_DB'] ?? 'auth';
        $authCol = $_ENV['AUTH_COLLECTION'] ?? 'users';
        $client  = \Flight::bangron()->getClient();

        if (!$client->dbExists($authDb)) {
            $client->createDB($authDb);
        }
        if (!$client->collectionExists($authDb, $authCol)) {
            $client->createCollection($authDb, $authCol);
        }

        $users = $client->selectCollection($authDb, $authCol);

        if ($users->findOne(['username' => $username])) {
            \Flight::json(['error' => true, 'message' => 'Username sudah digunakan'], 409);
            return;
        }

        // ensure schema role relation
        if (method_exists($users, 'setSchema')) {
            try {
                $users->setSchema([
                    'username'=>['required'=>true,'type'=>'string','unique'=>true],
                    'email'=>['type'=>'email','unique'=>true],
                    'name'=>['type'=>'string'],
                    'password_hash'=>['type'=>'string','hidden'=>true],
                    'role'=>['required'=>true,'type'=>'relation','relation'=>['db'=>'auth','collection'=>'roles','field'=>'_id','display'=>'name','type'=>'one'],'default'=>'user'],
                    'roles'=>['type'=>'array','hidden'=>true],
                    'active'=>['type'=>'bool','default'=>true],
                    'created_at'=>['type'=>'datetime','readonly'=>true],
                ]);
                $users->saveConfiguration();
            } catch (\Throwable $e) {}
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $id   = $users->insert([
            'username'      => $username,
            'email'         => $email,
            'name'          => $body['name'] ?? $username,
            'password_hash' => $hash,
            'role'          => $role,
            'roles'         => [$role],
            'created_at'    => date('c'),
            'active'        => true,
        ]);

        Audit::log(
            \Flight::bangron()->getPath(),
            'auth.register',
            $authDb,
            $authCol,
            ['id' => $id, 'username' => $username, 'role'=>$role, 'roles' => [$role]],
            [],
            'ok'
        );

        LoggerFactory::auth()->info('User registered', ['username' => $username, 'role' => $role]);

        \Flight::json(['ok' => true, '_id' => $id], 201);
    }

    /**
     * POST /api/auth/login
     */
    public function login(): void
    {
        $body     = \Flight::request()->data->getData();
        $username = $body['username'] ?? $body['email'] ?? '';
        $password = $body['password'] ?? '';

        $authDb  = $_ENV['AUTH_DB'] ?? 'auth';
        $authCol = $_ENV['AUTH_COLLECTION'] ?? 'users';
        $client  = \Flight::bangron()->getClient();

        if (!$client->dbExists($authDb) || !$client->collectionExists($authDb, $authCol)) {
            \Flight::json([
                'error'   => true,
                'message' => 'Sistem autentikasi belum diinisialisasi – silakan registrasi terlebih dahulu',
            ], 503);
            return;
        }

        $users = $client->selectCollection($authDb, $authCol);
        $user  = $users->findOne(['$or' => [['username' => $username], ['email' => $username]]]);

        if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            Audit::log(
                \Flight::bangron()->getPath(),
                'auth.login_failed',
                $authDb,
                $authCol,
                ['username' => $username],
                [],
                'forbidden'
            );
            LoggerFactory::auth()->warning('Login failed', ['username' => $username]);
            \Flight::json(['error' => true, 'message' => 'Kredensial tidak valid'], 401);
            return;
        }

        if (isset($user['active']) && !$user['active']) {
            \Flight::json(['error' => true, 'message' => 'Akun dinonaktifkan'], 403);
            return;
        }

        $roleSingle = $user['role'] ?? null;
        $roles   = $user['roles'] ?? ($roleSingle ? [$roleSingle] : ['user']);
        if ($roleSingle && !in_array($roleSingle, $roles, true)) {
            $roles = array_unique(array_merge([$roleSingle], $roles));
        }
        $primaryRole = $roleSingle ?? ($roles[0] ?? 'user');
        $payload = [
            'sub'      => $user['_id'] ?? null,
            'username' => $user['username'] ?? null,
            'email'    => $user['email'] ?? null,
            'roles'    => $roles,
            'role'     => $primaryRole,
            'name'     => $user['name'] ?? $user['username'] ?? null,
        ];

        $secret     = Acl::getJwtSecret();
        $accessTtl  = (int)($_ENV['JWT_ACCESS_TTL'] ?? 900);
        $refreshTtl = (int)($_ENV['JWT_REFRESH_TTL'] ?? 2592000);
        $pair       = Jwt::issuePair($payload, $secret, $accessTtl, $refreshTtl);

        RefreshTokenStore::store(
            $client,
            $pair['refresh_jti'],
            $payload['sub'],
            time() + $refreshTtl,
            [
                'username'   => $payload['username'],
                'roles'      => $roles,
                'access_jti' => $pair['jti'],
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua'         => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]
        );

        Audit::log(
            \Flight::bangron()->getPath(),
            'auth.login',
            $authDb,
            $authCol,
            $payload,
            ['jti' => $pair['jti']],
            'ok'
        );

        LoggerFactory::auth()->info('User logged in', ['username' => $payload['username']]);

        unset($user['password_hash']);

        \Flight::json(array_merge([
            'ok'    => true,
            'user'  => $user,
            'roles' => $roles,
            'token' => $pair['access_token'],
        ], $pair));
    }

    /**
     * POST /api/auth/refresh
     */
    public function refresh(): void
    {
        $body         = \Flight::request()->data->getData();
        $refreshToken = $body['refresh_token'] ?? $body['refreshToken'] ?? '';

        if (!$refreshToken) {
            \Flight::json(['error' => true, 'message' => 'refresh_token wajib diisi'], 400);
            return;
        }

        $secret  = Acl::getJwtSecret();
        $payload = Jwt::decode($refreshToken, $secret, true);

        if (!$payload || ($payload['type'] ?? '') !== 'refresh') {
            \Flight::json(['error' => true, 'message' => 'Refresh token tidak valid'], 401);
            return;
        }

        $client = \Flight::bangron()->getClient();
        $jti    = $payload['jti'] ?? '';
        $stored = RefreshTokenStore::get($client, $jti);

        if (!$stored) {
            \Flight::json(['error' => true, 'message' => 'Refresh token sudah dicabut atau tidak ditemukan'], 401);
            return;
        }

        // Rotate: revoke old refresh token and blacklist old access JTI
        RefreshTokenStore::revoke($client, $jti);

        if (!empty($payload['access_jti'])) {
            TokenBlacklist::revoke($client, $payload['access_jti'], [
                'reason' => 'refresh_rotate',
                'exp'    => $payload['exp'] ?? null,
            ]);
        }

        // Issue new token pair
        $newPayload = [
            'sub'      => $payload['sub'] ?? null,
            'username' => $payload['username'] ?? null,
            'email'    => $payload['email'] ?? null,
            'roles'    => $payload['roles'] ?? ['user'],
            'role'     => $payload['role'] ?? 'user',
            'name'     => $payload['name'] ?? null,
        ];

        $accessTtl  = (int)($_ENV['JWT_ACCESS_TTL'] ?? 900);
        $refreshTtl = (int)($_ENV['JWT_REFRESH_TTL'] ?? 2592000);
        $pair       = Jwt::issuePair($newPayload, $secret, $accessTtl, $refreshTtl);

        RefreshTokenStore::store(
            $client,
            $pair['refresh_jti'],
            $newPayload['sub'],
            time() + $refreshTtl,
            [
                'username'    => $newPayload['username'] ?? null,
                'roles'       => $newPayload['roles'] ?? [],
                'rotated_from' => $jti,
            ]
        );

        Audit::log(
            \Flight::bangron()->getPath(),
            'auth.refresh',
            'auth',
            'refresh_tokens',
            $newPayload,
            ['old_jti' => $jti, 'new_jti' => $pair['refresh_jti']],
            'ok'
        );

        LoggerFactory::auth()->info('Token refreshed', ['sub' => $newPayload['sub']]);

        \Flight::json(array_merge(['ok' => true], $pair));
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(): void
    {
        $body    = \Flight::request()->data->getData();
        $headers = AclMiddleware::getAllHeaders();
        $auth    = $headers['authorization'] ?? $headers['Authorization'] ?? '';
        $client  = \Flight::bangron()->getClient();
        $revoked = 0;

        // Revoke access token from Authorization header
        if (stripos($auth, 'bearer ') === 0) {
            $token   = trim(substr($auth, 7));
            $payload = Jwt::decode($token, Acl::getJwtSecret(), false);
            if ($payload && !empty($payload['jti'])) {
                TokenBlacklist::revoke($client, $payload['jti'], [
                    'reason' => 'logout',
                    'sub'    => $payload['sub'] ?? null,
                    'exp'    => $payload['exp'] ?? null,
                ]);
                $revoked++;
            }
        }

        // Revoke refresh token if provided in body
        $refreshToken = $body['refresh_token'] ?? null;
        if ($refreshToken) {
            $rp = Jwt::decode($refreshToken, Acl::getJwtSecret(), false);
            if ($rp && !empty($rp['jti'])) {
                RefreshTokenStore::revoke($client, $rp['jti']);
                TokenBlacklist::revoke($client, $rp['jti'], ['reason' => 'logout_refresh']);
                $revoked++;
            }
        }

        LoggerFactory::auth()->info('User logged out', ['revoked' => $revoked]);

        \Flight::json(['ok' => true, 'revoked' => $revoked]);
    }

    /**
     * POST /api/auth/revoke
     */
    public function revoke(): void
    {
        $body = \Flight::request()->data->getData();
        $jti  = $body['jti'] ?? '';

        if (!$jti) {
            \Flight::json(['error' => true, 'message' => 'jti wajib diisi'], 400);
            return;
        }

        $client = \Flight::bangron()->getClient();
        TokenBlacklist::revoke($client, $jti, [
            'reason' => $body['reason'] ?? 'manual',
            'by'     => 'api',
        ]);
        RefreshTokenStore::revoke($client, $jti);

        LoggerFactory::security()->info('Token revoked', ['jti' => $jti, 'reason' => $body['reason'] ?? 'manual']);

        \Flight::json(['ok' => true, 'jti' => $jti]);
    }

    /**
     * GET /api/auth/blacklist
     */
    public function blacklist(): void
    {
        $client = \Flight::bangron()->getClient();
        TokenBlacklist::purgeExpired($client);
        $list = TokenBlacklist::list($client, 200);

        \Flight::json(['data' => $list, 'count' => count($list)]);
    }

    /**
     * GET /api/auth/tokens
     */
    public function tokens(): void
    {
        $client = \Flight::bangron()->getClient();
        RefreshTokenStore::purgeExpired($client);
        $col    = $client->selectCollection('auth', 'refresh_tokens');
        $tokens = $col->find(['revoked' => false], ['password_hash' => 0], ['created_at' => -1], 200);

        \Flight::json(['data' => $tokens, 'count' => count($tokens)]);
    }

    /**
     * GET /api/auth/me
     */
    public function me(): void
    {
        $token = AclMiddleware::bearerToken();

        if (!$token) {
            \Flight::json(['error' => true, 'message' => 'Token bearer tidak ditemukan'], 401);
            return;
        }

        $payload = Jwt::decode($token, Acl::getJwtSecret(), true);

        if (!$payload) {
            \Flight::json(['error' => true, 'message' => 'Token tidak valid atau sudah kadaluarsa'], 401);
            return;
        }

        // Blacklist check
        if (!empty($payload['jti'])) {
            $client = \Flight::bangron()->getClient();
            if (TokenBlacklist::isRevoked($client, $payload['jti'])) {
                \Flight::json(['error' => true, 'message' => 'Token sudah dicabut'], 401);
                return;
            }
        }

        \Flight::json(['ok' => true, 'user' => $payload]);
    }
}