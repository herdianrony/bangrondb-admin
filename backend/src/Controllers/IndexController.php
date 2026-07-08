<?php
declare(strict_types=1);

namespace App\Controllers;

class IndexController
{
    /**
     * GET /api/@db/indexes — Get index metrics for a database.
     */
    public function index(string $db): void
    {
        $metrics = \Flight::bangron()->getIndexMetrics($db);

        \Flight::json(['data' => $metrics]);
    }

    /**
     * POST /api/@db/@collection/indexes — Create an index on a collection field.
     */
    public function store(string $db, string $collection): void
    {
        $body = \Flight::request()->data->getData();
        $field = $body['field'] ?? '';
        $name = $body['name'] ?? null;

        \Flight::bangron()->createIndex($db, $collection, $field, $name);

        \Flight::json(['ok' => true]);
    }

    /**
     * DELETE /api/@db/indexes/@name — Drop an index by name.
     */
    public function destroy(string $db, string $name): void
    {
        \Flight::bangron()->dropIndex($db, $name);

        \Flight::json(['ok' => true]);
    }
}