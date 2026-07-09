<?php
declare(strict_types=1);

namespace App\Controllers;

use Flight;

class ApiKeyController
{
    private function client()
    {
        return Flight::bangron()->getClient();
    }

    private function ensure(): void
    {
        $c = $this->client();
        if (!$c->dbExists('auth')) $c->createDB('auth');
        if (!$c->collectionExists('auth','api_keys')) {
            $c->createCollection('auth','api_keys');
            $col = $c->selectCollection('auth','api_keys');
            if (method_exists($col,'setSchema')) {
                $col->setSchema([
                    'name'=>['required'=>true,'type'=>'string'],
                    'key_hash'=>['required'=>true,'type'=>'string','unique'=>true,'hidden'=>true],
                    'key_prefix'=>['type'=>'string','label'=>'Prefix'],
                    'user_id'=>['type'=>'string','label'=>'User'],
                    'role'=>['type'=>'string','label'=>'Role','default'=>'api'],
                    'scopes'=>['type'=>'array','label'=>'Scopes'],
                    'active'=>['type'=>'bool','default'=>true],
                    'expires_at'=>['type'=>'datetime','label'=>'Expires'],
                    'last_used_at'=>['type'=>'datetime','readonly'=>true],
                    'created_at'=>['type'=>'datetime','readonly'=>true],
                ]);
                $col->saveConfiguration();
            }
        }
    }

    public function index(): void
    {
        $this->ensure();
        $col = $this->client()->selectCollection('auth','api_keys');
        $list = $col->find([], ['key_hash'=>0], ['created_at'=>-1], 200);
        Flight::json(['data'=>$list,'count'=>count($list)]);
    }

    public function store(): void
    {
        $this->ensure();
        $body = Flight::request()->data->getData();
        $plain = 'brk_'.bin2hex(random_bytes(24));
        $hash = hash('sha256', $plain);
        $doc = [
            '_id' => 'ak_'.bin2hex(random_bytes(8)),
            'name' => $body['name'] ?? 'API Key '.date('Y-m-d'),
            'key_hash' => $hash,
            'key_prefix' => substr($plain,0,12).'…',
            'user_id' => \App\Security\SessionAuth::id() ?? ($body['user_id'] ?? null),
            'role' => $body['role'] ?? 'api',
            'scopes' => $body['scopes'] ?? $body['permissions'] ?? ['read'],
            'active' => true,
            'created_at' => date('c'),
            'expires_at' => !empty($body['expires_days']) ? date('c', time()+((int)$body['expires_days']*86400)) : null,
        ];
        $col = $this->client()->selectCollection('auth','api_keys');
        $col->save($doc);
        // return plain key ONCE
        $doc['api_key'] = $plain;
        unset($doc['key_hash']);
        Flight::json(['ok'=>true,'data'=>$doc,'warning'=>'Simpan API key ini sekarang – tidak akan ditampilkan lagi'],201);
    }

    public function destroy(string $id): void
    {
        $this->ensure();
        $col = $this->client()->selectCollection('auth','api_keys');
        $n = $col->remove(['_id'=>$id]);
        Flight::json(['ok'=>true,'deleted'=>$n]);
    }
}
