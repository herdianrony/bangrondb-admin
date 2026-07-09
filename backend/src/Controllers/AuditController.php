<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Security\Audit;

class AuditController
{
    /**
     * GET /api/audit/logs — Query audit logs with filter, limit, skip.
     */
    public function index(): void
    {
        $query = \Flight::request()->query;
        $filter = json_decode($query['filter'] ?? '{}', true) ?: [];
        $limit = (int) ($query['limit'] ?? 100);
        $skip = (int) ($query['skip'] ?? 0);

        $result = Audit::query(\Flight::bangron()->getPath(), $filter, $limit, $skip);

        \Flight::json($result);
    }

    /**
     * GET /api/audit/stats — Return audit log statistics.
     */
    public function stats(): void
    {
        $result = Audit::query(\Flight::bangron()->getPath(), [], 1, 0);

        \Flight::json([
            'total_logged' => $result['total'] ?? 0,
            'db' => 'system',
            'collection' => 'audit_logs',
        ]);
    }
}