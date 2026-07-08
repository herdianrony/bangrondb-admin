<?php
declare(strict_types=1);

namespace App\Security;

use App\Logging\LoggerFactory;
use BangronDB\Client;
use Throwable;

class Audit
{
    private static ?Client $client = null;

    public static function init(string $dbPath): void
    {
        if (self::$client === null) {
            self::$client = new Client($dbPath);
            try {
                if (!self::$client->dbExists('system')) {
                    self::$client->createDB('system');
                }
                if (!self::$client->collectionExists('system', 'audit_logs')) {
                    self::$client->createCollection('system', 'audit_logs');
                    $col = self::$client->selectCollection('system', 'audit_logs');
                    try { $col->createIndex('created_at'); } catch(Throwable $e){}
                    try { $col->createIndex('user_id'); } catch(Throwable $e){}
                    try { $col->createIndex('action'); } catch(Throwable $e){}
                }
            } catch(Throwable $e){}
        }
    }

    public static function log(
        string $dbPath,
        string $action,
        string $db,
        string $collection = '',
        ?array $user = null,
        array $meta = [],
        ?string $status = 'ok'
    ): void {
        try {
            self::init($dbPath);
            if (!self::$client) return;
            // avoid recursion
            if ($db === 'system' && $collection === 'audit_logs') return;

            $col = $col = self::$client->selectCollection('system', 'audit_logs');
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'cli';
            if (is_string($ip) && str_contains($ip, ',')) $ip = trim(explode(',', $ip)[0]);

            $record = [
                'created_at' => date('c'),
                'ts'         => time(),
                'action'     => $action,
                'db'         => $db,
                'collection' => $collection,
                'user_id'    => $user['id'] ?? $user['_id'] ?? null,
                'username'   => $user['username'] ?? null,
                'roles'      => $user['roles'] ?? [],
                'ip'         => $ip,
                'ua'         => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'method'     => $_SERVER['REQUEST_METHOD'] ?? null,
                'path'       => $_SERVER['REQUEST_URI'] ?? null,
                'status'     => $status,
                'meta'       => $meta,
            ];

            $col->insert($record);

            // ─── Monolog: forward security/audit events ───────────
            try {
                $logger = LoggerFactory::security();
                $level = match($status) {
                    'forbidden'  => \Monolog\Logger::WARNING,
                    'error'      => \Monolog\Logger::ERROR,
                    default      => \Monolog\Logger::INFO,
                };
                $logger->log($level, 'audit.' . $action, [
                    'db'         => $db,
                    'collection' => $collection,
                    'user_id'    => $record['user_id'],
                    'username'   => $record['username'],
                    'roles'      => $record['roles'],
                    'ip'         => $ip,
                    'status'     => $status,
                ]);
            } catch (Throwable $e) {
                // Monolog failure must never break the app
            }
        } catch(Throwable $e) {
            // silent – never break app because audit failed
        }
    }

    public static function query(string $dbPath, array $filter = [], int $limit = 100, int $skip = 0): array
    {
        try {
            self::init($dbPath);
            $col = self::$client->selectCollection('system', 'audit_logs');
            $cursor = $col->find($filter)->sort(['ts' => -1])->skip($skip)->limit($limit);
            return [
                'data'  => $cursor->toArray(),
                'total' => $col->count($filter),
            ];
        } catch(Throwable $e) {
            return ['data'=>[],'total'=>0,'error'=>$e->getMessage()];
        }
    }
}