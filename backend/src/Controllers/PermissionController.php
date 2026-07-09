<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Security\PermissionRegistry;
use BangronDB\Client;
use Throwable;
use Flight;

class PermissionController
{
    private function client(): Client
    {
        return Flight::bangron()->getClient();
    }

    public function index(): void
    {
        $client = $this->client();
        PermissionRegistry::seed($client);
        $list = PermissionRegistry::all($client);
        $grouped = [];
        foreach ($list as $p) {
            $g = $p['group'] ?? 'custom';
            $grouped[$g][] = $p;
        }
        ksort($grouped);
        Flight::json(['data'=>$list,'grouped'=>$grouped,'count'=>count($list)]);
    }

    public function store(): void
    {
        $body = Flight::request()->data->getData();
        $name = strtolower(trim($body['name'] ?? ''));
        if (!$name || !preg_match('/^[a-z0-9_.\:\*\-]+$/', $name)) {
            Flight::json(['error'=>true,'message'=>'Invalid permission key'],400); return;
        }
        $client = $this->client();
        PermissionRegistry::ensure($client);
        $col = $client->selectCollection('auth','permissions');
        if ($col->findOne(['$or'=>[['_id'=>$name],['name'=>$name]]])) {
            Flight::json(['error'=>true,'message'=>'Permission sudah ada'],409); return;
        }
        $doc = [
            '_id'=>$name,
            'name'=>$name,
            'label'=>$body['label'] ?? ucwords(str_replace(['_','.','-',':'], ' ', $name)),
            'group'=>$body['group'] ?? 'custom',
            'description'=>$body['description'] ?? '',
            'is_system'=>false,
            'created_at'=>date('c'),
        ];
        $col->save($doc);
        Flight::json(['ok'=>true,'permission'=>$doc],201);
    }

    public function update(string $name): void
    {
        $body = Flight::request()->data->getData();
        $client = $this->client();
        $col = $client->selectCollection('auth','permissions');
        $p = $col->findOne(['$or'=>[['_id'=>$name],['name'=>$name]]]);
        if (!$p) { Flight::json(['error'=>true,'message'=>'not found'],404); return; }
        if (!empty($p['is_system'])) { Flight::json(['error'=>true,'message'=>'System permission tidak bisa diedit'],403); return; }
        foreach(['label','group','description'] as $f){ if(isset($body[$f])) $p[$f]=$body[$f]; }
        $col->save($p);
        Flight::json(['ok'=>true]);
    }

    public function destroy(string $name): void
    {
        $client = $this->client();
        $col = $client->selectCollection('auth','permissions');
        $p = $col->findOne(['$or'=>[['_id'=>$name],['name'=>$name]]]);
        if (!$p) { Flight::json(['ok'=>true,'deleted'=>0]); return; }
        if (!empty($p['is_system'])) { Flight::json(['error'=>true,'message'=>'Tidak dapat menghapus system permission'],403); return; }
        $rolesCol = $client->selectCollection('auth','roles');
        $used = $rolesCol->find(['permissions'=>['$in'=>[$name]]]);
        if (count($used)>0) {
            Flight::json(['error'=>true,'message'=>'Permission dipakai '.count($used).' role','used_by'=>array_column($used,'name')],409); return;
        }
        $n = $col->remove(['$or'=>[['_id'=>$name],['name'=>$name]]]);
        Flight::json(['ok'=>true,'deleted'=>$n]);
    }
}
