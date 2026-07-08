<?php
declare(strict_types=1);

namespace App\Controllers;

class DatabaseController
{
    /**
     * GET /api/databases
     */
    public function index(): void
    {
        \Flight::json(['data' => \Flight::bangron()->listDatabases()]);
    }

    /**
     * POST /api/databases
     */
    public function store(): void
    {
        $body = \Flight::request()->data->getData();
        $name = $body['name'] ?? '';

        if (!$name) {
            \Flight::json(['error' => true, 'message' => 'Nama database wajib diisi'], 400);
            return;
        }

        \Flight::bangron()->createDatabase($name);
        \Flight::json(['ok' => true, 'name' => $name], 201);
    }

    /**
     * DELETE /api/databases/@name
     */
    public function destroy(string $name): void
    {
        \Flight::bangron()->dropDatabase($name);
        \Flight::json(['ok' => true]);
    }

    /**
     * POST /api/databases/@old/rename
     */
    public function rename(string $old): void
    {
        $body   = \Flight::request()->data->getData();
        $newName = $body['new_name'] ?? '';

        if (!$newName) {
            \Flight::json(['error' => true, 'message' => 'new_name wajib diisi'], 400);
            return;
        }

        \Flight::bangron()->renameDatabase($old, $newName);
        \Flight::json(['ok' => true]);
    }
}