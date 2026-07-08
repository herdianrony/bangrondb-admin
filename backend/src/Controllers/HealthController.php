<?php
declare(strict_types=1);

namespace App\Controllers;

class HealthController
{
    /**
     * GET /api/@db/health — Get health report, metrics, and integrity check.
     */
    public function show(string $db): void
    {
        $result = \Flight::bangron()->health($db);

        \Flight::json($result);
    }

    /**
     * POST /api/@db/vacuum — Run VACUUM on a database.
     */
    public function vacuum(string $db): void
    {
        \Flight::bangron()->vacuum($db);

        \Flight::json(['ok' => true]);
    }

    /**
     * GET /api/@db/metrics — Get performance and collection metrics.
     */
    public function metrics(string $db): void
    {
        $result = \Flight::bangron()->metrics($db);

        \Flight::json($result);
    }
}