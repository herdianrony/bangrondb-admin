<?php
declare(strict_types=1);

namespace App\Controllers;

class SchemaController
{
    /**
     * GET /api/@db/@collection/schema
     */
    public function show(string $db, string $collection): void
    {
        \Flight::json(\Flight::bangron()->getSchema($db, $collection));
    }

    /**
     * POST /api/@db/@collection/schema
     */
    public function store(string $db, string $collection): void
    {
        $body   = \Flight::request()->data->getData();
        $schema = $body['schema'] ?? [];

        if (empty($schema)) {
            \Flight::json(['error' => true, 'message' => 'Schema wajib diisi'], 400);
            return;
        }

        \Flight::bangron()->setSchema($db, $collection, $schema);
        \Flight::json(['ok' => true]);
    }

    /**
     * POST /api/@db/@collection/validate
     */
    public function validate(string $db, string $collection): void
    {
        $body   = \Flight::request()->data->getData();
        $result = \Flight::bangron()->validateDocument($db, $collection, $body);
        \Flight::json($result);
    }
}