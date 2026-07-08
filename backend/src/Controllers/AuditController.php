<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Security\Audit;

class AuditController
{
    private function dbPath(): string
    {
        return $_ENV['DB_PATH'] ?? dirname(__DIR__, 2) . '/storage/data';
    }

    /**
     * GET /api/audit/logs — Query audit logs with filter, limit, skip.
     */
    public function index(): void
    {
        $query = \Flight::request()->query;
        $filter = json_decode($query['filter'] ?? '{}', true) ?: [];
        $limit = (int) ($query['limit'] ?? 100);
        $skip = (int) ($query['skip'] ?? 0);

        $result = Audit::query($this->dbPath(), $filter, $limit, $skip);

        \Flight::json($result);
    }

    /**
     * GET /api/audit/stats — Return audit log statistics.
     */
    public function stats(): void
    {
        $result = Audit::query($this->dbPath(), [], 1, 0);

        \Flight::json([
            'total_logged' => $result['total'] ?? 0,
            'db' => 'system',
            'collection' => 'audit_logs',
        ]);
    }
}