<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Inertia\SetupWizard;

class InertiaController
{
    private string $dbPath;

    public function __construct()
    {
        $this->dbPath = $_ENV['DB_PATH'] ?? dirname(__DIR__, 3) . '/storage/data';
    }

    /**
     * GET /
     *
     * If setup is needed → render standalone setup wizard.
     * Otherwise → render normal Inertia dashboard.
     */
    public function index(): void
    {
        // Check if setup is needed
        $wizard = new SetupWizard($this->dbPath);
        if ($wizard->isSetupNeeded()) {
            $wizard->render();
            return;
        }

        \Flight::inertia()->render('Dashboard/Index', [
            'stats' => \Flight::bangron()->dashboardStats()
        ]);
    }

    /**
     * GET /app/@path
     *
     * If setup is needed → redirect to /.
     * Otherwise → render Inertia page.
     */
    public function page(string $path): void
    {
        // Guard: redirect to setup if not initialized
        $wizard = new SetupWizard($this->dbPath);
        if ($wizard->isSetupNeeded()) {
            header('Location: /');
            exit;
        }

        // Explicit /setup route → redirect to / (wizard handles it)
        if ($path === 'setup') {
            header('Location: /');
            exit;
        }

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