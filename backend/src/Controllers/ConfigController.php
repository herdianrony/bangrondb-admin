<?php
declare(strict_types=1);

namespace App\Controllers;

class ConfigController
{
    /**
     * GET /api/@db/@collection/config — Load collection configuration.
     */
    public function show(string $db, string $collection): void
    {
        $config = \Flight::bangron()->getCollectionConfig($db, $collection);

        \Flight::json($config);
    }

    /**
     * POST /api/@db/@collection/id-mode — Set the ID generation mode for a collection.
     */
    public function idMode(string $db, string $collection): void
    {
        $body = \Flight::request()->data->getData();
        $mode = $body['mode'] ?? 'auto';
        $prefix = $body['prefix'] ?? null;

        \Flight::bangron()->setIdMode($db, $collection, $mode, $prefix);

        \Flight::json(['ok' => true]);
    }

    /**
     * POST /api/@db/@collection/config/save — Persist the current collection configuration.
     */
    public function save(string $db, string $collection): void
    {
        \Flight::bangron()->saveConfiguration($db, $collection);

        \Flight::json(['ok' => true]);
    }

    /**
     * POST /api/@db/transaction — Run a multi-operation transaction.
     */
    public function transaction(string $db): void
    {
        $body = \Flight::request()->data->getData();
        $operations = $body['operations'] ?? [];

        $result = \Flight::bangron()->runTransaction($db, $operations);

        \Flight::json($result);
    }
}