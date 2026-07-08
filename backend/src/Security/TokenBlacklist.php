<?php
declare(strict_types=1);

namespace App\Security;

use BangronDB\Client;

class TokenBlacklist
{
    const DB = 'auth';
    const COLLECTION = 'token_blacklist';

    public static function ensure(Client $client): void
    {
        if(!$client->dbExists(self::DB)) $client->createDB(self::DB);
        if(!$client->collectionExists(self::DB, self::COLLECTION)) $client->createCollection(self::DB, self::COLLECTION);
    }

    public static function isRevoked(Client $client, string $jti): bool
    {
        try {
            self::ensure($client);
            $col = $client->selectCollection(self::DB, self::COLLECTION);
            return $col->findOne(['jti'=>$jti]) !== null;
        } catch (\Throwable $e) { return false; }
    }

    public static function revoke(Client $client, string $jti, array $meta = []): void
    {
        self::ensure($client);
        $col = $client->selectCollection(self::DB, self::COLLECTION);
        if(!$col->findOne(['jti'=>$jti])){
            $col->insert(array_merge([
                'jti'=>$jti,
                'revoked_at'=>date('c'),
                'exp'=> $meta['exp'] ?? null,
            ], $meta));
        }
    }

    public static function list(Client $client, int $limit=100): array
    {
        self::ensure($client);
        $col = $client->selectCollection(self::DB, self::COLLECTION);
        return $col->find([], [], ['revoked_at'=>-1], $limit);
    }

    public static function purgeExpired(Client $client): int
    {
        self::ensure($client);
        $col = $client->selectCollection(self::DB, self::COLLECTION);
        $now = time();
        $all = $col->find([]);
        $deleted = 0;
        foreach($all as $d){
            if(!empty($d['exp']) && $d['exp'] < $now){
                $col->remove(['_id'=>$d['_id']]);
                $deleted++;
            }
        }
        return $deleted;
    }
}
