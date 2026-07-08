<?php
declare(strict_types=1);

namespace App\Controllers;

class SoftDeleteController
{
    /**
     * POST /api/@db/@collection/soft-deletes/toggle — Enable or disable soft deletes.
     */
    public function toggle(string $db, string $collection): void
    {
        $body = \Flight::request()->data->getData();
        $enabled = (bool) ($body['enabled'] ?? true);

        \Flight::bangron()->toggleSoftDeletes($db, $collection, $enabled);

        \Flight::json(['ok' => true]);
    }

    /**
     * POST /api/@db/@collection/restore — Restore soft-deleted documents.
     */
    public function restore(string $db, string $collection): void
    {
        $body = \Flight::request()->data->getData();
        $filter = $body['filter'] ?? [];

        $n = \Flight::bangron()->restore($db, $collection, $filter);

        \Flight::json(['ok' => true, 'restored' => $n]);
    }

    /**
     * POST /api/@db/@collection/force-delete — Permanently delete documents.
     */
    public function forceDelete(string $db, string $collection): void
    {
        $body = \Flight::request()->data->getData();
        $filter = $body['filter'] ?? [];

        $n = \Flight::bangron()->forceDelete($db, $collection, $filter);

        \Flight::json(['ok' => true, 'deleted' => $n]);
    }
}