<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Middleware\AclMiddleware;
use App\Security\Acl;

class DocumentController
{
    /**
     * GET /api/@db/@collection/documents
     *
     * Supports query params: filter, projection, sort, limit, skip,
     * with_trashed, only_trashed. Applies ACL row + field filtering.
     */
    public function index(string $db, string $collection): void
    {
        if (!AclMiddleware::guard($db, $collection, 'read')) {
            return;
        }

        $req          = \Flight::request()->query;
        $filter       = json_decode($req['filter'] ?? '{}', true) ?: [];
        $projection   = json_decode($req['projection'] ?? 'null', true);
        $sort         = json_decode($req['sort'] ?? '{}', true) ?: [];
        $limit        = (int)($req['limit'] ?? 50);
        $skip         = (int)($req['skip'] ?? 0);
        $withTrashed  = filter_var($req['with_trashed'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $onlyTrashed  = filter_var($req['only_trashed'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Row-level ACL filter
        $acl        = \Flight::get('acl_config') ?? [];
        $roles      = \Flight::get('acl_roles') ?? [];
        $aclUser    = \Flight::get('acl_user') ?? [];
        $userPayload = $aclUser['payload'] ?? $aclUser;

        if (!empty($acl['enabled'])) {
            $rowFilter = Acl::rowFilter($roles, $acl, $userPayload);
            if ($rowFilter) {
                $filter = Acl::mergeCriteria($filter, $rowFilter);
            }
        }

        $res = \Flight::bangron()->findDocuments(
            $db,
            $collection,
            $filter,
            $projection,
            $sort,
            $limit,
            $skip,
            $withTrashed,
            $onlyTrashed
        );

        // Field-level ACL filtering
        if (!empty($acl['enabled'])) {
            foreach ($res['data'] as &$doc) {
                $doc = Acl::filterFields($doc, $roles, $acl);
            }
            unset($doc);
        }

        \Flight::json($res);
    }

    /**
     * GET /api/@db/@collection/documents/@id
     */
    public function show(string $db, string $collection, string $id): void
    {
        if (!AclMiddleware::guard($db, $collection, 'read')) {
            return;
        }

        $doc  = \Flight::bangron()->findOne($db, $collection, ['_id' => $id]);
        $acl  = \Flight::get('acl_config') ?? [];
        $roles = \Flight::get('acl_roles') ?? [];

        if (!empty($acl['enabled']) && $doc) {
            $doc = Acl::filterFields($doc, $roles, $acl);
        }

        \Flight::json(['data' => $doc]);
    }

    /**
     * POST /api/@db/@collection/documents
     */
    public function store(string $db, string $collection): void
    {
        if (!AclMiddleware::guard($db, $collection, 'create')) {
            return;
        }

        $body = \Flight::request()->data->getData();
        $id   = \Flight::bangron()->insertDocument($db, $collection, $body);

        AclMiddleware::audit('document.insert', $db, $collection, [
            '_id'    => $id,
            'fields' => array_keys($body),
        ]);

        \Flight::json(['ok' => true, '_id' => $id], 201);
    }

    /**
     * PUT /api/@db/@collection/documents/@id
     */
    public function update(string $db, string $collection, string $id): void
    {
        if (!AclMiddleware::guard($db, $collection, 'update')) {
            return;
        }

        $body  = \Flight::request()->data->getData();
        $merge = $body['_merge'] ?? true;
        unset($body['_merge']);

        $n = \Flight::bangron()->updateDocument($db, $collection, ['_id' => $id], $body, $merge);

        AclMiddleware::audit('document.update', $db, $collection, [
            '_id'      => $id,
            'modified' => $n,
            'fields'   => array_keys($body),
        ]);

        \Flight::json(['ok' => true, 'modified' => $n]);
    }

    /**
     * DELETE /api/@db/@collection/documents
     *
     * Uses query param ?filter={} and ?force=true.
     */
    public function destroy(string $db, string $collection): void
    {
        if (!AclMiddleware::guard($db, $collection, 'delete')) {
            return;
        }

        $req    = \Flight::request()->query;
        $filter = json_decode($req['filter'] ?? '{}', true) ?: [];
        $force  = filter_var($req['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $count = \Flight::bangron()->removeDocuments($db, $collection, $filter, $force);

        AclMiddleware::audit('document.delete', $db, $collection, [
            'filter'  => $filter,
            'deleted' => $count,
            'force'   => $force,
        ]);

        \Flight::json(['ok' => true, 'deleted' => $count]);
    }

    /**
     * POST /api/@db/@collection/save
     *
     * Upsert (insert or update) a document.
     */
    public function save(string $db, string $collection): void
    {
        if (!AclMiddleware::guard($db, $collection, 'create')) {
            return;
        }

        $body = \Flight::request()->data->getData();
        $id   = \Flight::bangron()->saveDocument($db, $collection, $body);

        AclMiddleware::audit('document.save', $db, $collection, [
            '_id'    => $id,
            'fields' => array_keys($body),
        ]);

        \Flight::json(['ok' => true, '_id' => $id]);
    }

    /**
     * POST /api/@db/@collection/count
     */
    public function count(string $db, string $collection): void
    {
        if (!AclMiddleware::guard($db, $collection, 'read')) {
            return;
        }

        $body = \Flight::request()->data->getData();
        $c    = \Flight::bangron()->countDocuments($db, $collection, $body['filter'] ?? []);

        \Flight::json(['count' => $c]);
    }

    /**
     * POST /api/@db/@collection/query
     *
     * Accepts a JSON body with filter, projection, sort, limit, skip.
     * Applies ACL row-level and field-level filtering.
     */
    public function query(string $db, string $collection): void
    {
        if (!AclMiddleware::guard($db, $collection, 'read')) {
            return;
        }

        $body   = \Flight::request()->data->getData();
        $filter = $body['filter'] ?? [];

        // Row-level ACL filter
        $acl         = \Flight::get('acl_config') ?? [];
        $roles       = \Flight::get('acl_roles') ?? [];
        $aclUser     = \Flight::get('acl_user') ?? [];
        $userPayload = $aclUser['payload'] ?? $aclUser;

        if (!empty($acl['enabled'])) {
            $rf = Acl::rowFilter($roles, $acl, $userPayload);
            if ($rf) {
                $filter = Acl::mergeCriteria($filter, $rf);
            }
        }

        $res = \Flight::bangron()->findDocuments(
            $db,
            $collection,
            $filter,
            $body['projection'] ?? null,
            $body['sort'] ?? [],
            $body['limit'] ?? 50,
            $body['skip'] ?? 0
        );

        // Field-level ACL filtering
        if (!empty($acl['enabled'])) {
            foreach ($res['data'] as &$d) {
                $d = Acl::filterFields($d, $roles, $acl);
            }
            unset($d);
        }

        \Flight::json($res);
    }
}