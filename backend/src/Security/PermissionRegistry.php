<?php
declare(strict_types=1);

namespace App\Security;

use BangronDB\Client;

class PermissionRegistry
{
    const DB = 'auth';
    const COLLECTION = 'permissions';

    public static function ensure(Client $client): void
    {
        if (!$client->dbExists(self::DB)) $client->createDB(self::DB);
        if (!$client->collectionExists(self::DB, self::COLLECTION)) {
            $client->createCollection(self::DB, self::COLLECTION);
        }
        $col = $client->selectCollection(self::DB, self::COLLECTION);
        if (method_exists($col, 'setSchema')) {
            $col->setSchema([
                'name' => ['required'=>true,'type'=>'string','unique'=>true,'label'=>'Permission key','min'=>2,'regex'=>'/^[a-z0-9_\.\:\*\-]+$/'],
                'label' => ['type'=>'string','label'=>'Label'],
                'group' => ['type'=>'string','label'=>'Group','default'=>'custom'],
                'description' => ['type'=>'text','label'=>'Description','rows'=>2],
                'is_system' => ['type'=>'bool','label'=>'System','default'=>false,'readonly'=>true],
                'created_at' => ['type'=>'datetime','readonly'=>true],
            ]);
            $col->saveConfiguration();
        }
    }

    public static function seed(Client $client): int
    {
        self::ensure($client);
        $col = $client->selectCollection(self::DB, self::COLLECTION);
        $defaults = [
            ['_id'=>'read','name'=>'read','label'=>'Read / View','group'=>'crud','description'=>'Find, findOne, count, get','is_system'=>true],
            ['_id'=>'find','name'=>'find','label'=>'Find','group'=>'crud','is_system'=>true],
            ['_id'=>'count','name'=>'count','label'=>'Count','group'=>'crud','is_system'=>true],
            ['_id'=>'create','name'=>'create','label'=>'Create','group'=>'crud','description'=>'Insert / save','is_system'=>true],
            ['_id'=>'insert','name'=>'insert','label'=>'Insert','group'=>'crud','is_system'=>true],
            ['_id'=>'update','name'=>'update','label'=>'Update','group'=>'crud','is_system'=>true],
            ['_id'=>'delete','name'=>'delete','label'=>'Delete','group'=>'crud','is_system'=>true],
            ['_id'=>'remove','name'=>'remove','label'=>'Remove','group'=>'crud','is_system'=>true],
            ['_id'=>'manage_schema','name'=>'manage_schema','label'=>'Manage Schema','group'=>'admin','is_system'=>true],
            ['_id'=>'manage_acl','name'=>'manage_acl','label'=>'Manage ACL','group'=>'admin','is_system'=>true],
            ['_id'=>'manage_index','name'=>'manage_index','label'=>'Manage Indexes','group'=>'admin','is_system'=>true],
            ['_id'=>'manage_hooks','name'=>'manage_hooks','label'=>'Manage Hooks','group'=>'admin','is_system'=>true],
            ['_id'=>'export','name'=>'export','label'=>'Export Data','group'=>'data','description'=>'Export JSON/CSV','is_system'=>false],
            ['_id'=>'import','name'=>'import','label'=>'Import Data','group'=>'data','description'=>'Bulk import','is_system'=>false],
            ['_id'=>'publish','name'=>'publish','label'=>'Publish','group'=>'workflow','is_system'=>false],
            ['_id'=>'approve','name'=>'approve','label'=>'Approve','group'=>'workflow','is_system'=>false],
            ['_id'=>'archive','name'=>'archive','label'=>'Archive','group'=>'workflow','is_system'=>false],
            ['_id'=>'restore','name'=>'restore','label'=>'Restore','group'=>'data','description'=>'Restore soft-deleted','is_system'=>false],
            ['_id'=>'force_delete','name'=>'force_delete','label'=>'Force Delete','group'=>'admin','is_system'=>false],
            ['_id'=>'*','name'=>'*','label'=>'Full Access (*)','group'=>'system','description'=>'All permissions','is_system'=>true],
        ];
        $n=0;
        foreach ($defaults as $p) {
            $p['created_at'] = $p['created_at'] ?? date('c');
            try {
                if (!$col->findOne(['_id'=>$p['_id']]) && !$col->findOne(['name'=>$p['name']])) {
                    $col->save($p);
                    $n++;
                }
            } catch (\Throwable $e) {}
        }
        return $n;
    }

    public static function all(Client $client): array
    {
        self::ensure($client);
        $col = $client->selectCollection(self::DB, self::COLLECTION);
        try {
            return $col->find([], null, ['group'=>1,'name'=>1], 500);
        } catch (\Throwable $e) { return []; }
    }

    public static function exists(Client $client, string $permission): bool
    {
        if ($permission === '*') return true;
        self::ensure($client);
        $col = $client->selectCollection(self::DB, self::COLLECTION);
        return $col->findOne(['$or'=>[['_id'=>$permission],['name'=>$permission]]]) !== null;
    }

    public static function isAllowed(string $action, array $granted): bool
    {
        if (in_array('*', $granted, true)) return true;
        if (in_array($action, $granted, true)) return true;
        $map = [
            'find'=>'read','findOne'=>'read','count'=>'read','get'=>'read',
            'insert'=>'create','save'=>'create',
            'remove'=>'delete',
        ];
        if (isset($map[$action]) && in_array($map[$action], $granted, true)) return true;
        return false;
    }
}
