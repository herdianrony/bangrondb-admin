<?php
declare(strict_types=1);

namespace App\Controllers;

class CollectionController
{
    /**
     * GET /api/@db/collections
     */
    public function index(string $db): void
    {
        \Flight::json(['data' => \Flight::bangron()->listCollections($db)]);
    }

    /**
     * POST /api/@db/collections
     */
    public function store(string $db): void
    {
        $body       = \Flight::request()->data->getData();
        $collection = $body['name'] ?? '';

        if (!$collection) {
            \Flight::json(['error' => true, 'message' => 'Nama koleksi wajib diisi'], 400);
            return;
        }

        \Flight::bangron()->createCollection($db, $collection);
        \Flight::json(['ok' => true], 201);
    }

    /**
     * DELETE /api/@db/collections/@collection
     */
    public function destroy(string $db, string $collection): void
    {
        \Flight::bangron()->dropCollection($db, $collection);
        \Flight::json(['ok' => true]);
    }

    /**
     * POST /api/@db/collections/@collection/rename
     */
    public function rename(string $db, string $collection): void
    {
        $body   = \Flight::request()->data->getData();
        $newName = $body['new_name'] ?? '';

        if (!$newName) {
            \Flight::json(['error' => true, 'message' => 'new_name wajib diisi'], 400);
            return;
        }

        \Flight::bangron()->renameCollection($db, $collection, $newName);
        \Flight::json(['ok' => true]);
    }
}