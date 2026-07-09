<?php
declare(strict_types=1);

namespace App\Security;

class SessionAuth
{
    const SESSION_KEY = 'bangron_auth';
    const CSRF_KEY = 'bangron_csrf';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            session_set_cookie_params([
                'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
                'path' => '/',
                'domain' => '',
                'secure' => $secure && (($_ENV['SESSION_SECURE_COOKIE'] ?? 'false') === 'true'),
                'httponly' => true,
                'samesite' => $_ENV['SESSION_SAME_SITE'] ?? 'Lax',
            ]);
            session_start();
        }
    }

    /**
     * Simpan user ke session – struktur sesuai request user:
     * [
     *   '_id' => 'id user',
     *   'username' => 'superadmin',
     *   'role' => '_id role',
     *   'login_at' => time()
     * ]
     */
    public static function login(array $userDoc): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = [
            '_id'      => $userDoc['_id'] ?? null,
            'username' => $userDoc['username'] ?? null,
            'email'    => $userDoc['email'] ?? null,
            'name'     => $userDoc['name'] ?? $userDoc['username'] ?? null,
            // SINGLE role relation
            'role'     => $userDoc['role'] ?? ($userDoc['roles'][0] ?? 'user'),
            // BC array tetap disimpan
            'roles'    => $userDoc['roles'] ?? [$userDoc['role'] ?? 'user'],
            'login_at' => time(),
            'ip'       => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua'       => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];
        // CSRF
        $_SESSION[self::CSRF_KEY] = bin2hex(random_bytes(32));
    }

    public static function user(): ?array
    {
        self::start();
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): string
    {
        $u = self::user();
        return $u['role'] ?? 'guest';
    }

    public static function roles(): array
    {
        $u = self::user();
        if (!$u) return ['guest'];
        // prefer roles array BC, fallback single role
        if (!empty($u['roles']) && is_array($u['roles'])) return $u['roles'];
        return [$u['role'] ?? 'guest'];
    }

    public static function id(): ?string
    {
        return self::user()['_id'] ?? null;
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION[self::CSRF_KEY])) {
            $_SESSION[self::CSRF_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CSRF_KEY];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::start();
        $sess = $_SESSION[self::CSRF_KEY] ?? '';
        return $token && $sess && hash_equals($sess, $token);
    }

    public static function logout(): void
    {
        self::start();
        unset($_SESSION[self::SESSION_KEY], $_SESSION[self::CSRF_KEY]);
        session_regenerate_id(true);
        session_destroy();
    }
}
