<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Security\SessionAuth;
use Flight;

class WebAuthMiddleware
{
    /**
     * Guard web routes – redirect to /auth/login if not authenticated via Session
     * Usage: Flight::route('GET /dashboard', function(){ WebAuthMiddleware::handle(); ... });
     * Or register as before filter.
     */
    public static function handle(?string $redirectTo = '/auth/login'): ?array
    {
        $user = SessionAuth::user();
        if (!$user) {
            // Inertia / AJAX request?
            $isInertia = !empty($_SERVER['HTTP_X_INERTIA']);
            $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json');

            if ($isInertia || $wantsJson || (Flight::request()->method !== 'GET')) {
                Flight::json([
                    'error' => true,
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Silakan login terlebih dahulu',
                    'redirect' => $redirectTo
                ], 401);
                Flight::stop();
                return null;
            }

            // normal browser → redirect
            $_SESSION['url_intended'] = $_SERVER['REQUEST_URI'] ?? '/';
            Flight::redirect($redirectTo);
            Flight::stop();
            return null;
        }

        // share to Flight / Inertia
        Flight::set('webUser', $user);
        return $user;
    }

    /**
     * Check permission for current session user
     * role → resource → action
     */
    public static function can(string $action, string $db = '', string $collection = ''): bool
    {
        $user = SessionAuth::user();
        if (!$user) return false;
        $role = $user['role'] ?? 'guest';
        // superadmin bypass
        if ($role === 'superadmin') return true;

        // if db/collection provided → check ACL
        if ($db && $collection) {
            try {
                $col = Flight::bangron()->getCollection($db, $collection);
                $acl = \App\Security\Acl::loadCached($col, $db, $collection);
                return \App\Security\Acl::can([$role], $action, $acl);
            } catch (\Throwable $e) {
                return false;
            }
        }

        // otherwise check role permissions from auth.roles
        try {
            $client = Flight::bangron()->getClient();
            $rolesCol = $client->selectCollection('auth','roles');
            $r = $rolesCol->findOne(['$or'=>[['name'=>$role],['_id'=>$role]]]);
            if (!$r) return false;
            $perms = $r['permissions'] ?? [];
            return \App\Security\PermissionRegistry::isAllowed($action, $perms);
        } catch (\Throwable $e) {
            return in_array($role, ['admin','superadmin'], true);
        }
    }

    /**
     * Abort 403 if cannot
     */
    public static function authorize(string $action, string $db = '', string $collection = ''): void
    {
        if (!self::can($action, $db, $collection)) {
            Flight::json([
                'error' => true,
                'code' => 'FORBIDDEN',
                'message' => "Anda tidak memiliki izin '$action'" . ($db ? " pada $db.$collection" : ''),
                'role' => SessionAuth::role(),
            ], 403);
            Flight::stop();
        }
    }
}
