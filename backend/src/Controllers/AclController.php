<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Security\Acl;
use App\Security\Audit;
use Throwable;

class AclController
{
    private function dbPath(): string
    {
        return $_ENV['DB_PATH'] ?? dirname(__DIR__, 2) . '/storage/data';
    }

    /**
     * GET /api/@db/@collection/acl — Load ACL configuration for a collection.
     */
    public function show(string $db, string $collection): void
    {
        try {
            $col = \Flight::bangron()->getCollection($db, $collection);
            $acl = Acl::load($col);
            \Flight::json(['enabled' => $acl['enabled'] ?? false, 'acl' => $acl]);
        } catch (Throwable $e) {
            \Flight::json(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/@db/@collection/acl — Save ACL configuration and audit log.
     */
    public function store(string $db, string $collection): void
    {
        $body = \Flight::request()->data->getData();
        $aclIn = $body['acl'] ?? $body;

        $col = \Flight::bangron()->getCollection($db, $collection);
        Acl::save($col, $aclIn);

        Audit::log(
            $this->dbPath(),
            'acl.save',
            $db,
            $collection,
            \Flight::get('acl_user') ?? [],
            ['acl' => $aclIn]
        );

        \Flight::json(['ok' => true]);
    }

    /**
     * POST /api/@db/@collection/acl/test — Test whether given roles can perform an action.
     */
    public function test(string $db, string $collection): void
    {
        $body = \Flight::request()->data->getData();
        $action = $body['action'] ?? 'read';
        $roles = $body['roles'] ?? ['guest'];

        $col = \Flight::bangron()->getCollection($db, $collection);
        $acl = Acl::load($col);
        $allowed = Acl::can((array) $roles, $action, $acl);

        \Flight::json([
            'allowed' => $allowed,
            'roles' => $roles,
            'action' => $action,
        ]);
    }
}