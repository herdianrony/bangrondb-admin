<?php
declare(strict_types=1);

namespace App\Services;

use BangronDB\Client;
use BangronDB\Database;
use BangronDB\Collection;
use Throwable;

class BangronService
{
    private Client $client;
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
        $options = [
            'encryption_key' => $_ENV['ENCRYPTION_KEY'] ?? null,
            'query_logging' => filter_var($_ENV['QUERY_LOGGING'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'performance_monitoring' => filter_var($_ENV['PERFORMANCE_MONITORING'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
        $this->client = new Client($path, array_filter($options, fn($v)=>$v!==null && $v!==false));
    }

    public function dashboardStats(): array
    {
        $dbs = $this->listDatabases();
        $collections = 0; $documents = 0; $size = 0;
        foreach ($dbs as $dbName) {
            try {
                $cols = $this->listCollections($dbName);
                $collections += count($cols);
                foreach ($cols as $c) {
                    try {
                        $col = $this->col($dbName,$c);
                        $documents += $col->count();
                    } catch(Throwable $e){}
                }
                $file = rtrim($this->path,'/').'/'.$dbName.'.bangron';
                if (file_exists($file)) $size += filesize($file);
                $file2 = rtrim($this->path,'/').'/'.$dbName.'.sqlite';
                if (file_exists($file2)) $size += filesize($file2);
            } catch(Throwable $e){}
        }
        return [
            'databases' => count($dbs),
            'collections' => $collections,
            'documents' => $documents,
            'total_size_mb' => round($size/1024/1024,2),
            'php_version' => PHP_VERSION,
            'health' => ['status'=>'ok']
        ];
    }

    public function listDatabases(): array
    {
        if (method_exists($this->client, 'listDBs')) return $this->client->listDBs();
        // fallback scan folder
        $files = glob(rtrim($this->path,'/').'/*.{bangron,sqlite,db}', GLOB_BRACE) ?: [];
        return array_values(array_map(fn($f)=> pathinfo($f, PATHINFO_FILENAME), $files));
    }

    public function createDatabase(string $name): void
    {
        if (!$name) throw new \InvalidArgumentException('Nama database wajib');
        $this->client->createDB($name);
    }
    public function dropDatabase(string $name): void { $this->client->dropDB($name); }
    public function renameDatabase(string $old, string $new): void { $this->client->renameDB($old,$new); }

    public function listCollections(string $db): array
    {
        if (!$this->client->dbExists($db)) return [];
        return $this->client->listCollections($db);
    }
    public function createCollection(string $db, string $collection): void
    {
        if (!$this->client->dbExists($db)) $this->client->createDB($db);
        $this->client->createCollection($db, $collection);
    }
    public function dropCollection(string $db, string $collection): void { $this->client->dropCollection($db,$collection); }
    public function renameCollection(string $db, string $old, string $new): void { $this->client->renameCollection($db,$old,$new); }

    private function col(string $db, string $collection): Collection
    {
        return $this->client->selectCollection($db,$collection);
    }
    private function db(string $db): Database
    {
        return $this->client->selectDB($db);
    }

    public function findDocuments(string $db, string $collection, array $filter=[], ?array $projection=null, array $sort=[], int $limit=50, int $skip=0, bool $withTrashed=false, bool $onlyTrashed=false): array
    {
        $col = $this->col($db,$collection);
        $cursor = $col->find($filter, $projection);
        if ($onlyTrashed && method_exists($cursor,'onlyTrashed')) $cursor = $cursor->onlyTrashed();
        elseif ($withTrashed && method_exists($cursor,'withTrashed')) $cursor = $cursor->withTrashed();
        if ($sort) $cursor = $cursor->sort($sort);
        if ($skip) $cursor = $cursor->skip($skip);
        if ($limit) $cursor = $cursor->limit($limit);
        $data = $cursor->toArray();
        $total = $col->count($filter);
        return ['data'=>$data,'total'=>$total,'limit'=>$limit,'skip'=>$skip];
    }

    public function findOne(string $db, string $col, array $filter) { return $this->col($db,$col)->findOne($filter); }
    public function insertDocument(string $db, string $col, array $doc) { return $this->col($db,$col)->insert($doc); }
    public function saveDocument(string $db, string $col, array $doc) { return $this->col($db,$col)->save($doc); }
    public function updateDocument(string $db, string $col, array $criteria, array $data, bool $merge=true) { return $this->col($db,$col)->update($criteria,$data,$merge); }
    public function removeDocuments(string $db, string $col, array $filter, bool $force=false) {
        $c = $this->col($db,$col);
        if ($force && method_exists($c,'forceDelete')) return $c->forceDelete($filter);
        return $c->remove($filter);
    }
    public function countDocuments(string $db, string $col, array $filter=[]) { return $this->col($db,$col)->count($filter); }

    public function setEncryption(string $db, string $col, ?string $key, array $searchable=[], bool $hash=true): void {
        $c = $this->col($db,$col);
        if ($key) $c->setEncryptionKey($key);
        if ($searchable) $c->setSearchableFields($searchable, $hash);
        $c->saveConfiguration();
    }

    public function getSchema(string $db, string $col): array {
        $c = $this->col($db,$col);
        // schema tidak ada getter publik? kembalikan config
        $cfg = method_exists($this->db($db),'loadCollectionConfig') ? $this->db($db)->loadCollectionConfig($col) : [];
        // enhanced schema is stored directly in $cfg['schema'] – includes UI metadata
        return ['schema'=>$cfg['schema'] ?? $c->getSchema(), 'config'=>$cfg];
    }
    public function setSchema(string $db, string $col, array $schema): void {
        $c=$this->col($db,$col);
        // set enhanced schema directly – BangronDB core now supports enhanced types natively
        $c->setSchema($schema);
        // auto-apply enhanced metadata: indexes, searchable
        try {
            // use SchemaMapper helper if available, otherwise manual
            if (class_exists(\App\Support\SchemaMapper::class)) {
                $indexes = \App\Support\SchemaMapper::extractIndexes($schema);
                foreach ($indexes as $f) { try { $c->createIndex($f); } catch(\Throwable $e) {} }
                $searchable = \App\Support\SchemaMapper::extractSearchable($schema);
                if ($searchable && !empty($_ENV['ENCRYPTION_KEY'])) {
                    try { $c->setEncryptionKey($_ENV['ENCRYPTION_KEY']); $c->setSearchableFields($searchable, true); } catch(\Throwable $e){}
                }
            } else {
                // manual fallback
                foreach ($schema as $field => $def) {
                    if (!empty($def['index']) || !empty($def['sortable']) || !empty($def['filterable']) || !empty($def['unique'])) {
                        try { $c->createIndex($field); } catch(\Throwable $e){}
                    }
                }
            }
        } catch(\Throwable $e){}
        $c->saveConfiguration();
    }
    public function validateDocument(string $db, string $col, array $doc): array {
        try {
            $this->col($db,$col)->validate($doc);
            return ['valid'=>true];
        } catch (Throwable $e) {
            return ['valid'=>false,'error'=>$e->getMessage()];
        }
    }

    public function toggleSoftDeletes(string $db, string $col, bool $enabled): void {
        $c=$this->col($db,$col);
        $c->useSoftDeletes($enabled);
        $c->saveConfiguration();
    }
    public function restore(string $db, string $col, array $filter) {
        return $this->col($db,$col)->restore($filter);
    }
    public function forceDelete(string $db, string $col, array $filter) {
        return $this->col($db,$col)->forceDelete($filter);
    }

    public function listHooks(string $db, string $col): array {
        // hooks registry internal – return example list
        return [
            'beforeInsert','afterInsert','beforeUpdate','afterUpdate','beforeRemove','afterRemove'
        ];
    }

    public function populate(string $db, string $collection, array $body): array
    {
        $col = $this->col($db,$collection);
        $filter = $body['filter'] ?? [];
        $field = $body['local_field'] ?? 'author_id';
        $foreign = $body['foreign'] ?? null; // e.g. "app.users" or object
        $as = $body['as'] ?? null;
        if (!$foreign) throw new \InvalidArgumentException('foreign required e.g. app.users');
        // foreign can be "db.collection"
        if (is_string($foreign) && str_contains($foreign,'.')) {
            [$fdb,$fcol] = explode('.', $foreign, 2);
            $foreignCol = $this->client->selectCollection($fdb,$fcol);
        } else {
            $foreignCol = $col; // fallback
        }
        $cursor = $col->find($filter);
        if ($as) {
            $result = $cursor->populate($field, $foreignCol, ['as'=>$as])->toArray();
        } else {
            $result = $cursor->populate($field, $foreignCol)->toArray();
        }
        return $result;
    }

    public function getIndexMetrics(string $db): array {
        return $this->db($db)->getIndexMetrics();
    }
    public function createIndex(string $db, string $col, string $field, ?string $name=null): void {
        $this->col($db,$col)->createIndex($field, $name);
    }
    public function dropIndex(string $db, string $indexName): void {
        $this->db($db)->dropIndex($indexName);
    }

    public function health(string $db): array {
        $d = $this->db($db);
        return [
            'metrics' => method_exists($d,'getHealthMetrics') ? $d->getHealthMetrics() : [],
            'report' => method_exists($d,'getHealthReport') ? $d->getHealthReport() : [],
            'integrity' => method_exists($d,'checkIntegrity') ? $d->checkIntegrity() : 'ok',
        ];
    }
    public function metrics(string $db): array {
        $d = $this->db($db);
        return [
            'performance' => method_exists($d,'getPerformanceMetrics') ? $d->getPerformanceMetrics() : [],
            'collections' => method_exists($d,'getCollectionMetrics') ? $d->getCollectionMetrics() : [],
        ];
    }
    public function vacuum(string $db): void { $this->db($db)->vacuum(); }

    public function getCollectionConfig(string $db, string $col): array {
        $d=$this->db($db);
        return method_exists($d,'loadCollectionConfig') ? ($d->loadCollectionConfig($col) ?? []) : [];
    }
    public function setIdMode(string $db, string $col, string $mode, ?string $prefix=null): void {
        $c=$this->col($db,$col);
        match($mode) {
            'manual' => $c->setIdModeManual(),
            'prefix' => $prefix ? $c->setIdModePrefix($prefix) : $c->setIdModeAuto(),
            default => $c->setIdModeAuto(),
        };
        $c->saveConfiguration();
    }
    public function saveConfiguration(string $db, string $col): void { $this->col($db,$col)->saveConfiguration(); }

    // ---- helpers ----
    public function getCollection(string $db, string $collection): Collection
    {
        return $this->col($db, $collection);
    }
    public function getClient(): Client
    {
        return $this->client;
    }
    public function getPath(): string { return $this->path; }

    public function runTransaction(string $db, array $operations): array
    {
        $d = $this->db($db);
        $conn = $d->connection;
        $conn->beginTransaction();
        try {
            $results = [];
            foreach ($operations as $op) {
                $col = $this->col($db, $op['collection'] ?? '');
                $action = $op['action'] ?? 'find';
                $results[] = match($action) {
                    'insert' => $col->insert($op['document'] ?? []),
                    'update' => $col->update($op['filter'] ?? [], $op['data'] ?? []),
                    'remove' => $col->remove($op['filter'] ?? []),
                    default => $col->find($op['filter'] ?? [])->limit(5)->toArray(),
                };
            }
            $conn->commit();
            return ['ok'=>true,'results'=>$results];
        } catch (Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
