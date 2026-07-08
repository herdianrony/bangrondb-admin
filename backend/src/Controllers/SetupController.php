<?php
declare(strict_types=1);

namespace App\Controllers;

use BangronDB\Client;
use Throwable;

class SetupController
{
    private static function dbPath(): string
    {
        return $_ENV['DB_PATH'] ?? dirname(__DIR__, 3) . '/storage/data';
    }

    /**
     * GET /api/setup/status
     */
    public function status(): void
    {
        $dbPath     = self::dbPath();
        $client     = new Client($dbPath);
        $hasAuth    = $client->dbExists('auth') && $client->collectionExists('auth', 'users');
        $adminExists = false;
        $userCount  = 0;

        if ($hasAuth) {
            try {
                $u          = $client->selectCollection('auth', 'users');
                $userCount  = $u->count();
                $adminExists = $u->findOne(['roles' => ['$in' => ['superadmin', 'admin']]]) !== null
                    || $u->findOne(['username' => 'superadmin']) !== null;
            } catch (Throwable $e) {
                // auth DB partially initialized
            }
        }

        \Flight::json([
            'needs_setup'  => !$adminExists,
            'has_auth_db'  => $hasAuth,
            'user_count'   => $userCount,
            'admin_exists' => $adminExists,
            'version'      => '2.0.0',
        ]);
    }

    /**
     * POST /api/setup/initialize
     *
     * Creates admin user, auth DB with roles, app DB with seed collections & schemas.
     */
    public function initialize(): void
    {
        $body       = \Flight::request()->data->getData();
        $adminUser  = trim($body['username'] ?? 'admin');
        $adminEmail = trim($body['email'] ?? 'admin@bangron.studio');
        $adminPass  = $body['password'] ?? '';
        $appDb      = trim($body['app_db'] ?? 'app');
        $seeds      = $body['seed'] ?? ['blog', 'tasks'];

        // Validate
        if (empty($adminUser) || strlen($adminUser) < 3) {
            \Flight::json(['ok' => false, 'message' => 'Username harus minimal 3 karakter'], 400);
            return;
        }
        if (strlen($adminPass) < 8) {
            \Flight::json(['ok' => false, 'message' => 'Password harus minimal 8 karakter'], 400);
            return;
        }
        if (empty($appDb) || !preg_match('/^[a-z0-9_]+$/', $appDb)) {
            \Flight::json(['ok' => false, 'message' => 'Nama database hanya boleh huruf kecil, angka, dan underscore'], 400);
            return;
        }

        $dbPath = self::dbPath();
        $client = new Client($dbPath);

        try {
            // ── Auth database + roles ──
            if (!$client->dbExists('auth')) {
                $client->createDB('auth');
            }
            foreach (['roles', 'users'] as $c) {
                if (!$client->collectionExists('auth', $c)) {
                    $client->createCollection('auth', $c);
                }
            }

            $roles = $client->selectCollection('auth', 'roles');
            foreach ($this->seedRoles() as $r) {
                try { $roles->save($r); } catch (Throwable $e) {}
            }

            // ── Admin user ──
            $users    = $client->selectCollection('auth', 'users');
            $existing = $users->findOne(['$or' => [['username' => $adminUser], ['email' => $adminEmail]]]);

            if (!$existing) {
                $users->insert([
                    'username'      => $adminUser,
                    'email'         => $adminEmail,
                    'name'          => 'Administrator',
                    'password_hash' => password_hash($adminPass, PASSWORD_ARGON2ID),
                    'roles'         => ['superadmin'],
                    'active'        => true,
                    'created_at'    => date('c'),
                ]);
            }

            // ── Application database ──
            if (!$client->dbExists($appDb)) {
                $client->createDB($appDb);
            }

            // ── Seed collections with schemas ──
            $seedMap = $this->seedCollectionMap();
            foreach ($seeds as $seed) {
                if (!isset($seedMap[$seed])) continue;
                $cfg = $seedMap[$seed];
                $cName = $cfg['name'];

                if (!$client->collectionExists($appDb, $cName)) {
                    $client->createCollection($appDb, $cName);
                }

                // Save schema
                if (!empty($cfg['schema'])) {
                    try {
                        $col = $client->selectCollection($appDb, $cName);
                        if (method_exists($col, 'saveSchema')) {
                            $col->saveSchema($cfg['schema']);
                        }
                    } catch (Throwable $e) {
                        // schema save non-critical
                    }
                }

                // Seed sample documents
                if (!empty($cfg['documents'])) {
                    try {
                        $col = $client->selectCollection($appDb, $cName);
                        foreach ($cfg['documents'] as $doc) {
                            $col->insert($doc);
                        }
                    } catch (Throwable $e) {
                        // seed data non-critical
                    }
                }

                // Set ACL config
                try {
                    $col = $client->selectCollection($appDb, $cName);
                    if (method_exists($col, 'setCustomConfig')) {
                        $col->setCustomConfig('acl', [
                            'enabled'      => true,
                            'default_role' => 'guest',
                            'roles'        => [
                                'superadmin' => ['*'],
                                'admin'      => ['read', 'create', 'update', 'delete', 'manage_schema'],
                                'editor'     => ['read', 'create', 'update'],
                                'user'       => ['read'],
                                'guest'      => [],
                            ],
                            'field_rules' => [],
                            'row_filters' => [],
                            'api_keys'    => [],
                        ]);
                        $col->saveConfiguration();
                    }
                } catch (Throwable $e) {}
            }

            \Flight::json([
                'ok'      => true,
                'message' => 'Setup berhasil diselesaikan',
                'data' => [
                    'username'  => $adminUser,
                    'email'     => $adminEmail,
                    'databases' => ['auth', $appDb],
                    'collections' => array_map(fn($s) => $seedMap[$s]['name'] ?? $s, $seeds),
                ],
            ]);

        } catch (Throwable $e) {
            \Flight::json([
                'ok'      => false,
                'message' => 'Gagal menginisialisasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function seedRoles(): array
    {
        return [
            ['_id' => 'superadmin', 'name' => 'superadmin', 'label' => 'Super Administrator', 'permissions' => ['*'], 'is_system' => true],
            ['_id' => 'admin',      'name' => 'admin',      'label' => 'Administrator',         'permissions' => ['read', 'create', 'update', 'delete', 'manage_schema', 'manage_acl'], 'is_system' => true],
            ['_id' => 'editor',     'name' => 'editor',     'label' => 'Editor',                'permissions' => ['read', 'create', 'update'], 'is_system' => true],
            ['_id' => 'user',       'name' => 'user',       'label' => 'User',                  'permissions' => ['read'], 'is_system' => true],
            ['_id' => 'guest',      'name' => 'guest',      'label' => 'Guest',                 'permissions' => [], 'is_system' => true],
        ];
    }

    private function seedCollectionMap(): array
    {
        return [
            'blog' => [
                'name' => 'posts',
                'schema' => [
                    'title'         => ['type' => 'string',  'label' => 'Title',         'required' => true, 'min' => 3, 'max' => 200, 'searchable' => true, 'sortable' => true, 'index' => true],
                    'slug'          => ['type' => 'string',  'label' => 'Slug',          'required' => true, 'unique' => true],
                    'content'       => ['type' => 'text',    'label' => 'Content',       'required' => true],
                    'excerpt'       => ['type' => 'text',    'label' => 'Excerpt',       'rows' => 3],
                    'status'        => ['type' => 'enum',    'label' => 'Status',        'options' => ['draft', 'published', 'archived'], 'default' => 'draft', 'filterable' => true, 'sortable' => true, 'ui' => ['badge' => true, 'color' => ['draft' => 'gray', 'published' => 'green', 'archived' => 'amber']]],
                    'author'        => ['type' => 'string',  'label' => 'Author',        'filterable' => true],
                    'category'      => ['type' => 'string',  'label' => 'Category',      'filterable' => true, 'sortable' => true],
                    'tags'          => ['type' => 'tags',    'label' => 'Tags',          'filterable' => true],
                    'featured_image' => ['type' => 'url',    'label' => 'Featured Image'],
                    'published_at'  => ['type' => 'datetime', 'label' => 'Published At',  'sortable' => true],
                ],
                'documents' => [
                    ['title' => 'Getting Started with Bangron Studio', 'slug' => 'getting-started', 'content' => 'Bangron Studio is a powerful backend platform with an embedded document database. This guide will walk you through the basics.', 'status' => 'published', 'author' => 'admin', 'category' => 'tutorial', 'tags' => ['getting-started', 'tutorial'], 'published_at' => date('c')],
                    ['title' => 'Understanding Collections and Schemas', 'slug' => 'collections-schemas', 'content' => 'Collections are like tables in a relational database, but with flexible schemas. Each collection can have its own schema definition with field types, validation rules, and UI hints.', 'status' => 'published', 'author' => 'admin', 'category' => 'tutorial', 'tags' => ['collections', 'schema'], 'published_at' => date('c')],
                    ['title' => 'API Authentication Guide', 'slug' => 'api-authentication', 'content' => 'Bangron Studio uses JWT (JSON Web Tokens) for authentication. This guide covers login, token refresh, and protected endpoints.', 'status' => 'draft', 'author' => 'admin', 'category' => 'api', 'tags' => ['auth', 'jwt', 'api']],
                ],
            ],
            'tasks' => [
                'name' => 'tasks',
                'schema' => [
                    'title'       => ['type' => 'string',  'label' => 'Title',       'required' => true, 'min' => 2, 'searchable' => true, 'sortable' => true, 'index' => true],
                    'description' => ['type' => 'text',    'label' => 'Description', 'rows' => 4],
                    'status'      => ['type' => 'enum',    'label' => 'Status',      'options' => ['backlog', 'todo', 'in_progress', 'review', 'done'], 'default' => 'todo', 'filterable' => true, 'sortable' => true, 'index' => true, 'ui' => ['badge' => true, 'color' => ['backlog' => 'gray', 'todo' => 'slate', 'in_progress' => 'blue', 'review' => 'amber', 'done' => 'green']]],
                    'priority'    => ['type' => 'enum',    'label' => 'Priority',    'options' => ['low', 'medium', 'high', 'urgent'], 'default' => 'medium', 'filterable' => true, 'sortable' => true, 'ui' => ['badge' => true, 'color' => ['low' => 'gray', 'medium' => 'blue', 'high' => 'amber', 'urgent' => 'red']]],
                    'assignee'    => ['type' => 'string',  'label' => 'Assignee',    'filterable' => true],
                    'due_date'    => ['type' => 'date',    'label' => 'Due Date',    'sortable' => true, 'filterable' => true],
                    'tags'        => ['type' => 'tags',    'label' => 'Labels',      'filterable' => true],
                ],
                'documents' => [
                    ['title' => 'Design new landing page', 'status' => 'in_progress', 'priority' => 'high', 'assignee' => 'admin', 'tags' => ['design', 'frontend']],
                    ['title' => 'Setup CI/CD pipeline', 'status' => 'todo', 'priority' => 'medium', 'assignee' => 'admin', 'tags' => ['devops']],
                    ['title' => 'Write API documentation', 'status' => 'backlog', 'priority' => 'low', 'assignee' => 'admin', 'tags' => ['docs']],
                    ['title' => 'Fix login redirect bug', 'status' => 'done', 'priority' => 'urgent', 'assignee' => 'admin', 'tags' => ['bugfix']],
                    ['title' => 'Add dark mode toggle', 'status' => 'review', 'priority' => 'medium', 'assignee' => 'admin', 'tags' => ['feature', 'ui']],
                ],
            ],
            'products' => [
                'name' => 'products',
                'schema' => [
                    'name'          => ['type' => 'string', 'label' => 'Product Name', 'required' => true, 'min' => 1, 'max' => 200, 'searchable' => true, 'sortable' => true, 'index' => true],
                    'description'   => ['type' => 'text',   'label' => 'Description',  'rows' => 5],
                    'price'         => ['type' => 'float',  'label' => 'Price',        'required' => true, 'min' => 0, 'sortable' => true, 'filterable' => true],
                    'compare_price' => ['type' => 'float',  'label' => 'Compare Price','min' => 0],
                    'category'      => ['type' => 'string', 'label' => 'Category',     'filterable' => true, 'sortable' => true],
                    'sku'           => ['type' => 'string', 'label' => 'SKU',          'unique' => true],
                    'stock'         => ['type' => 'int',    'label' => 'Stock',        'default' => 0, 'min' => 0, 'sortable' => true],
                    'in_stock'      => ['type' => 'bool',   'label' => 'In Stock',     'default' => true, 'filterable' => true],
                    'images'        => ['type' => 'array',  'label' => 'Images'],
                    'tags'          => ['type' => 'tags',   'label' => 'Tags',         'filterable' => true],
                    'status'        => ['type' => 'enum',   'label' => 'Status',       'options' => ['active', 'draft', 'archived'], 'default' => 'active', 'filterable' => true, 'sortable' => true, 'ui' => ['badge' => true, 'color' => ['active' => 'green', 'draft' => 'gray', 'archived' => 'amber']]],
                ],
                'documents' => [
                    ['name' => 'Wireless Headphones', 'description' => 'Premium noise-cancelling wireless headphones with 40-hour battery life.', 'price' => 149.99, 'category' => 'electronics', 'sku' => 'WH-001', 'stock' => 45, 'in_stock' => true, 'status' => 'active', 'tags' => ['audio', 'wireless']],
                    ['name' => 'Mechanical Keyboard', 'description' => 'Compact 75% mechanical keyboard with hot-swappable switches and RGB backlighting.', 'price' => 89.99, 'category' => 'electronics', 'sku' => 'MK-002', 'stock' => 120, 'in_stock' => true, 'status' => 'active', 'tags' => ['keyboard', 'mechanical']],
                    ['name' => 'Ergonomic Mouse', 'description' => 'Vertical ergonomic mouse designed to reduce wrist strain during long work sessions.', 'price' => 59.99, 'category' => 'accessories', 'sku' => 'EM-003', 'stock' => 0, 'in_stock' => false, 'status' => 'active', 'tags' => ['mouse', 'ergonomic']],
                ],
            ],
            'users' => [
                'name' => 'users',
                'schema' => [
                    'name'      => ['type' => 'string',  'label' => 'Full Name',  'required' => true, 'min' => 2, 'searchable' => true, 'sortable' => true, 'index' => true],
                    'email'     => ['type' => 'email',   'label' => 'Email',      'required' => true, 'unique' => true, 'searchable' => true],
                    'username'  => ['type' => 'string',  'label' => 'Username',   'required' => true, 'unique' => true, 'min' => 3],
                    'role'      => ['type' => 'enum',    'label' => 'Role',       'options' => ['admin', 'editor', 'user'], 'default' => 'user', 'filterable' => true, 'sortable' => true, 'ui' => ['badge' => true, 'color' => ['admin' => 'red', 'editor' => 'blue', 'user' => 'gray']]],
                    'avatar'    => ['type' => 'url',     'label' => 'Avatar URL'],
                    'bio'       => ['type' => 'text',    'label' => 'Bio',        'rows' => 3],
                    'active'    => ['type' => 'bool',    'label' => 'Active',     'default' => true, 'filterable' => true],
                    'tags'      => ['type' => 'tags',    'label' => 'Tags',       'filterable' => true],
                ],
                'documents' => [
                    ['name' => 'John Doe', 'email' => 'john@example.com', 'username' => 'johndoe', 'role' => 'editor', 'active' => true, 'bio' => 'Full-stack developer'],
                    ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'username' => 'janesmith', 'role' => 'user', 'active' => true, 'bio' => 'Content writer'],
                    ['name' => 'Bob Wilson', 'email' => 'bob@example.com', 'username' => 'bobwilson', 'role' => 'user', 'active' => false, 'bio' => 'Designer'],
                ],
            ],
        ];
    }
}