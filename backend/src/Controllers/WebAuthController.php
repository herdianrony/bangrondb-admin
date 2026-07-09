<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Security\SessionAuth;
use App\Security\Audit;
use App\Logging\LoggerFactory;
use Flight;

class WebAuthController
{
    public function showLogin(): void
    {
        // if already logged in → dashboard
        if (SessionAuth::check()) {
            Flight::redirect('/');
            return;
        }
        Flight::inertia()->render('Auth/Login', [
            'csrf' => SessionAuth::csrfToken(),
        ]);
    }

    public function login(): void
    {
        $body = Flight::request()->data->getData();
        $username = trim($body['username'] ?? $body['email'] ?? '');
        $password = $body['password'] ?? '';
        $csrf = $body['_token'] ?? Flight::request()->getHeader('X-CSRF-Token');

        // CSRF check (skip in dev if needed)
        if (!empty($_ENV['CSRF_PROTECTION']) && $_ENV['CSRF_PROTECTION'] === 'true') {
            if (!SessionAuth::verifyCsrf($csrf)) {
                if (Flight::request()->isAjax()) {
                    Flight::json(['error'=>true,'message'=>'CSRF token mismatch'],419);
                    return;
                }
                $_SESSION['flash_error'] = 'CSRF token mismatch';
                Flight::redirect('/auth/login');
                return;
            }
        }

        $client = Flight::bangron()->getClient();
        $users = $client->selectCollection('auth','users');
        $user = $users->findOne(['$or'=>[['username'=>$username],['email'=>$username]]]);

        if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            LoggerFactory::auth()->warning('Web login failed', ['username'=>$username]);
            if (Flight::request()->isAjax()) {
                Flight::json(['error'=>true,'message'=>'Kredensial tidak valid'],401);
                return;
            }
            $_SESSION['flash_error'] = 'Username / password salah';
            Flight::redirect('/auth/login');
            return;
        }

        if (isset($user['active']) && !$user['active']) {
            Flight::json(['error'=>true,'message'=>'Akun dinonaktifkan'],403);
            return;
        }

        // pastikan role single terisi
        if (empty($user['role']) && !empty($user['roles'][0])) {
            $user['role'] = $user['roles'][0];
        }

        SessionAuth::login($user);

        Audit::log(
            defined('BANGRON_DB_PATH') ? BANGRON_DB_PATH : dirname(__DIR__,2).'/storage/data',
            'web.login',
            'auth','users',
            ['id'=>$user['_id'],'username'=>$user['username'],'role'=>$user['role'] ?? null],
            [],
            'ok'
        );

        LoggerFactory::auth()->info('Web user logged in', ['username'=>$user['username']]);

        // Inertia response
        if (Flight::request()->isAjax() || str_contains(Flight::request()->getHeader('Accept') ?? '', 'json')) {
            Flight::json([
                'ok'=>true,
                'redirect'=>'/',
                'user'=>[
                    '_id'=>$user['_id'],
                    'username'=>$user['username'],
                    'role'=>$user['role'] ?? null,
                    'name'=>$user['name'] ?? $user['username'],
                ]
            ]);
            return;
        }

        Flight::redirect('/');
    }

    public function logout(): void
    {
        $u = SessionAuth::user();
        SessionAuth::logout();
        if ($u) {
            LoggerFactory::auth()->info('Web user logged out', ['username'=>$u['username'] ?? null]);
        }
        // Inertia / normal redirect
        if (!empty($_SERVER['HTTP_X_INERTIA'])) {
            Flight::json(['ok'=>true,'redirect'=>'/auth/login']);
            return;
        }
        Flight::redirect('/auth/login');
    }

    public function me(): void
    {
        $u = SessionAuth::user();
        if (!$u) {
            Flight::json(['error'=>true,'message'=>'Unauthenticated'],401);
            return;
        }
        Flight::json(['ok'=>true,'user'=>$u]);
    }
}
