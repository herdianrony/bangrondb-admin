<?php
declare(strict_types=1);

namespace App\Controllers;

class InertiaController
{
    public function index(): void
    {
        \Flight::inertia()->render('Dashboard/Index', [
            'stats' => \Flight::bangron()->dashboardStats()
        ]);
    }

    public function page(string $path): void
    {
        $map = [
            'databases'    => 'Databases/Index',
            'collections'  => 'Collections/Index',
            'documents'    => 'Documents/Index',
            'query'        => 'Query/Index',
            'encryption'   => 'Encryption/Index',
            'schema'       => 'Schema/Index',
            'acl'          => 'Acl/Index',
            'users'        => 'Users/Index',
            'roles'        => 'Roles/Index',
            'tokens'       => 'Tokens/Index',
            'setup'        => 'Setup/Index',
            'soft-deletes' => 'SoftDeletes/Index',
            'hooks'        => 'Hooks/Index',
            'relations'    => 'Relations/Index',
            'indexes'      => 'Indexes/Index',
            'health'       => 'Health/Index',
            'config'       => 'Config/Index',
        ];
        $component = $map[explode('/', $path)[0] ?? ''] ?? 'Dashboard/Index';
        \Flight::inertia()->render($component, [
            'stats' => \Flight::bangron()->dashboardStats(),
            'path'  => $path
        ]);
    }
}