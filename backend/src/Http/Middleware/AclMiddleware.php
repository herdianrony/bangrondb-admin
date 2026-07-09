<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Security\Acl;
use App\Security\Audit;

class AclMiddleware
{
    /**
     * Check ACL for the given db/collection/action.
     * Stores roles, config, and user in Flight for downstream use.
     *
     * @return bool true if allowed (continue), false if forbidden (response sent).
     */
    public static function guard(string $db, string $collection, string $action): bool
    {
        try {
            $dbPath = \Flight::bangron()->getPath();
            Audit::init($dbPath);

            $col   = \Flight::bangron()->getCollection($db, $collection);
            $acl   = Acl::load($col);
            $headers = self::getAllHeaders();
            $jwtPayload = [];
            $roles = Acl::userRoles($headers, $acl, $jwtPayload);
            $user  = Acl::userFromRequest($headers, $acl);
            $allowed = Acl::can($roles, $action, $acl);

            Audit::log(
                $dbPath,
                'acl.check.' . $action,
                $db, $collection,
                $user,
                ['roles' => $roles, 'allowed' => $allowed, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null],
                $allowed ? 'allowed' : 'forbidden'
            );

            if (!$allowed) {
                \Flight::json([
                    'error'   => true,
                    'code'    => 'ACL_FORBIDDEN',
                    'message' => "Akses ditolak untuk action '$action' pada $db.$collection",
                    'roles'   => $roles,
                    'required'=> $action,
                    'hint'    => 'Kirim header X-Role: admin / editor / user, atau X-API-Key, atau Authorization: Bearer <jwt>',
                ], 403);
                return false;
            }

            \Flight::set('acl_roles', $roles);
            \Flight::set('acl_config', $acl);
            \Flight::set('acl_user', $user);
            return true;
        } catch (\Throwable $e) {
            // If collection doesn't exist, allow — let downstream handle 404
            return true;
        }
    }

    /**
     * Shorthand audit helper using Flight-stored ACL context.
     */
    public static function audit(string $action, string $db, string $collection, array $meta = [], string $status = 'ok'): void
    {
        $user = \Flight::get('acl_user') ?? ['roles' => \Flight::get('acl_roles') ?? ['guest']];
        $dbPath = \Flight::bangron()->getPath();
        Audit::log($dbPath, $action, $db, $collection, $user, $meta, $status);
    }

    /**
     * Get all headers from the request, normalized.
     */
    public static function getAllHeaders(): array
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $hk = str_replace('_', '-', substr($k, 5));
                $headers[$hk] = $v;
                $headers[strtolower($hk)] = $v;
            }
        }
        return $headers;
    }

    /**
     * Get the Authorization header value.
     */
    public static function getAuthHeader(): string
    {
        $headers = self::getAllHeaders();
        return $headers['authorization'] ?? $headers['Authorization'] ?? '';
    }

    /**
     * Extract Bearer token from request.
     */
    public static function bearerToken(): ?string
    {
        $auth = self::getAuthHeader();
        if (stripos($auth, 'bearer ') === 0) {
            return trim(substr($auth, 7));
        }
        return null;
    }
}