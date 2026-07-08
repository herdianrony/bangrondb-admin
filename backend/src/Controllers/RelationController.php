<?php
declare(strict_types=1);

namespace App\Controllers;

class RelationController
{
    /**
     * POST /api/@db/@collection/populate — Populate related documents.
     */
    public function populate(string $db, string $collection): void
    {
        $body = \Flight::request()->data->getData();

        $result = \Flight::bangron()->populate($db, $collection, $body);

        \Flight::json(['data' => $result]);
    }
}