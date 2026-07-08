<?php
declare(strict_types=1);

namespace App\Controllers;

class EncryptionController
{
    /**
     * POST /api/@db/@collection/encryption
     *
     * Sets encryption key and optional searchable fields for a collection.
     */
    public function store(string $db, string $collection): void
    {
        $body       = \Flight::request()->data->getData();
        $key        = $body['key'] ?? null;
        $searchable = $body['searchable'] ?? [];
        $hash       = $body['hash'] ?? true;

        \Flight::bangron()->setEncryption($db, $collection, $key, $searchable, (bool) $hash);
        \Flight::json(['ok' => true]);
    }
}