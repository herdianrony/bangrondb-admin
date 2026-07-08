<?php
declare(strict_types=1);

namespace App\Http\Middleware;

class CorsMiddleware
{
    /**
     * Handle CORS preflight and set headers.
     * Returns true if the request should continue, false if it was handled (OPTIONS).
     */
    public static function handle(): bool
    {
        $origin = $_ENV['CORS_ALLOWED_ORIGIN'] ?? '*';

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Inertia, X-Inertia-Version, X-Api-Key, X-Role, X-User-Role');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        return true;
    }
}