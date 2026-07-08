<?php
declare(strict_types=1);

namespace App\Controllers;

class HookController
{
    /**
     * GET /api/@db/@collection/hooks — List available hook events for a collection.
     */
    public function index(string $db, string $collection): void
    {
        $hooks = \Flight::bangron()->listHooks($db, $collection);

        \Flight::json(['data' => $hooks]);
    }
}