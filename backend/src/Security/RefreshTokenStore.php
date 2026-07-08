<?php
declare(strict_types=1);

namespace App\Security;

use BangronDB\Client;

class RefreshTokenStore
{
    const DB = 'auth';
    const COLLECTION = 'refresh_tokens';

    public static function ensure(Client $client): void
    {
        if(!$client->dbExists(self::DB)) $client->createDB(self::DB);
        if(!$client->collectionExists(self::DB, self::COLLECTION)) $client->createCollection(self::DB, self::COLLECTION);
    }

    public static function store(Client $client, string $jti, string $userId, int $exp, array $meta = []): void
    {
        self::ensure($client);
        $col = $client->selectCollection(self::DB, self::COLLECTION);
        $col->save(array_merge([
            '_id'=>$jti,
            'jti'=>$jti,
            'user_id'=>$userId,
            'exp'=>$exp,
            'created_at'=>date('c'),
            'revoked'=>false,
        ], $meta));
    }

    public static function get(Client $client, string $jti): ?array
    {
        self::ensure($client);
        $col = $client->selectCollection(self::DB, self::COLLECTION);
        return $col->findOne(['jti'=>$jti, 'revoked'=>false]);
    }

    public static function revoke(Client $client, string $jti): void
    {
        self::ensure($client);
        $col = $client->selectCollection(self::DB, self::COLLECTION);
        $doc = $col->findOne(['jti'=>$jti]);
        if($doc){
            $doc['revoked']=true;
            $doc['revoked_at']=date('c');
            $col->save($doc);
        }
    }

    public static function revokeUser(Client $client, string $userId): int
    {
        self::ensure($client);
        $col = $client->selectCollection(self::DB, self::COLLECTION);
        $tokens = $col->find(['user_id'=>$userId, 'revoked'=>false]);
        $n=0;
        foreach($tokens as $t){
            self::revoke($client, $t['jti']);
            $n++;
        }
        return $n;
    }

    public static function purgeExpired(Client $client): int
    {
        self::ensure($client);
        $col = $client->selectCollection(self::DB, self::COLLECTION);
        $now = time();
        $expired = $col->find(['exp'=>['$lt'=>$now]]);
        $n=0;
        foreach($expired as $e){
            $col->remove(['_id'=>$e['_id']]);
            $n++;
        }
        return $n;
    }
}
