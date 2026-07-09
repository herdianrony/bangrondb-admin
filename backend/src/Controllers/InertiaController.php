<?php
declare(strict_types=1);

namespace App\Controllers;

class InertiaController
{
    /**
     * GET /
     * Renders Dashboard (database list overview) or Setup if not initialized.
     */
    public function index(): void
    {
        $dbPath = defined('BANGRON_DB_PATH') ? BANGRON_DB_PATH : dirname(__DIR__, 2) . '/storage/data';

        if ($this->needsSetup($dbPath)) {
            \Flight::inertia()->render('Setup/Index', [
                'stats' => ['databases' => 0, 'collections' => 0, 'documents' => 0, 'total_size_mb' => 0, 'php_version' => PHP_VERSION, 'health' => ['status' => 'ok']],
                'auth' => null,
            ]);
            return;
        }

        $authUser = class_exists(\App\Security\SessionAuth::class) ? \App\Security\SessionAuth::user() : null;

        \Flight::inertia()->render('Dashboard/Index', [
            'stats' => \Flight::bangron()->dashboardStats(),
            'auth' => $authUser ? [
                'user' => $authUser,
                'role' => $authUser['role'] ?? null,
                'roles' => $authUser['roles'] ?? [],
            ] : null,
        ]);
    }

    /**
     * GET /setup
     */
    public function setup(): void
    {
        \Flight::inertia()->render('Setup/Index', []);
    }

    /**
     * GET /auth/login
     */
    public function authLogin(): void
    {
        \Flight::inertia()->render('Auth/Login', []);
    }

    /**
     * GET /auth/register
     */
    public function authRegister(): void
    {
        \Flight::inertia()->render('Auth/Register', []);
    }

    /**
     * GET /databases/@db
     * Shows a specific database with its collections.
     */
    public function database(string $db): void
    {
        $dbPath = defined('BANGRON_DB_PATH') ? BANGRON_DB_PATH : dirname(__DIR__, 2) . '/storage/data';

        if ($this->needsSetup($dbPath)) {
            header('Location: /');
            exit;
        }

        \Flight::inertia()->render('Databases/Show', [
            'db'     => $db,
            'stats'  => \Flight::bangron()->dashboardStats(),
        ]);
    }

    /**
     * GET /databases/@db/collections/@col
     * Shows documents inside a specific collection.
     */
    public function collection(string $db, string $col): void
    {
        $dbPath = defined('BANGRON_DB_PATH') ? BANGRON_DB_PATH : dirname(__DIR__, 2) . '/storage/data';

        if ($this->needsSetup($dbPath)) {
            header('Location: /');
            exit;
        }

        \Flight::inertia()->render('Collections/Show', [
            'db'    => $db,
            'col'   => $col,
            'stats' => \Flight::bangron()->dashboardStats(),
        ]);
    }

    /**
     * GET /@path — catch-all fallback for unknown routes.
     */
    public function fallback(string $path): void
    {
        \Flight::inertia()->render('Dashboard/Index', [
            'stats'  => \Flight::bangron()->dashboardStats(),
            'path'   => $path,
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