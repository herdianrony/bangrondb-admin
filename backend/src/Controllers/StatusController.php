<?php
declare(strict_types=1);

namespace App\Controllers;

class StatusController
{
    /**
     * GET /api/status — Return API status and capability info.
     */
    public function index(): void
    {
        \Flight::json([
            'name' => 'Bangron Studio API',
            'version' => '1.3.0-enhanced-schema',
            'php' => PHP_VERSION,
            'time' => date('c'),
            'databases' => \Flight::bangron()->listDatabases(),
            'schema_mode' => 'enhanced-native',
            'features' => [
                'types' => [
                    'string', 'text', 'email', 'password', 'url',
                    'int', 'float', 'bool', 'array', 'object', 'enum',
                    'date', 'datetime', 'relation', 'tags',
                ],
                'validation' => ['required', 'type', 'min', 'max', 'enum', 'regex', 'unique'],
                'ui_meta' => ['label', 'placeholder', 'icon', 'rows', 'readonly', 'hidden', 'badge', 'color'],
                'table' => ['filterable', 'sortable', 'index', 'searchable'],
                'relations' => true,
            ],
        ]);
    }

    /**
     * GET /api/@db/@collection/ssot — BC shim: proxy to schema endpoint.
     */
    public function ssotGet(string $db, string $collection): void
    {
        $data = \Flight::bangron()->getSchema($db, $collection);

        \Flight::json([
            'deprecated' => true,
            'message' => 'Endpoint SSOT sudah dihapus – gunakan /api/{db}/{col}/schema. Schema versi baru sekarang sudah native.',
            'schema' => $data['schema'] ?? [],
            'config' => $data['config'] ?? [],
        ]);
    }

    /**
     * PUT /api/@db/@collection/ssot — BC shim: proxy to setSchema.
     */
    public function ssotPut(string $db, string $collection): void
    {
        $body = \Flight::request()->data->getData();
        $schema = $body['ssot'] ?? $body['schema'] ?? $body;

        \Flight::bangron()->setSchema($db, $collection, $schema);

        \Flight::json([
            'ok' => true,
            'migrated' => 'ssot -> schema',
            'note' => 'SSOT sudah tidak digunakan, beralih ke schema native yang lebih lengkap',
        ]);
    }

    /**
     * POST /api/@db/@collection/ssot/@any — 410 Gone for any SSOT POST.
     */
    public function ssotAny(): void
    {
        \Flight::json([
            'error' => true,
            'message' => 'Endpoint SSOT sudah dihapus – gunakan POST /api/{db}/{col}/schema',
        ], 410);
    }

    /**
     * GET /api/ssot/@any — 410 Gone for root SSOT presets.
     */
    public function ssotRoot(string $any): void
    {
        \Flight::json([
            'error' => true,
            'deprecated' => true,
            'message' => 'API preset SSOT sudah dihapus. Schema sekarang bersifat dinamis melalui /api/{db}/{col}/schema',
        ], 410);
    }
}