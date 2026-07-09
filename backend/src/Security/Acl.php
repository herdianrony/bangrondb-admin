<?php
declare(strict_types=1);

namespace App\Security;

use BangronDB\Collection;

class Acl
{
    public const CONFIG_KEY = 'acl';

    public static function defaultConfig(): array
    {
        return [
            'enabled' => false,
            'default_role' => 'guest',
            'roles' => [
                'admin'  => ['*'],
                'editor' => ['read','find','count','create','update'],
                'user'   => ['read','find','count'],
                'guest'  => [],
            ],
            // field_rules: ['editor'=>['email'=>'deny', 'salary'=>'deny']] 
            // or allowlist: ['editor'=>['__mode'=>'allow','name'=>'allow','email'=>'allow']]
            'field_rules' => [],
            'row_filters' => [],
            'api_keys' => [],
        ];
    }

    public static function load(Collection $col): array
    {
        if (method_exists($col, 'getCustomConfig')) {
            $cfg = $col->getCustomConfig(self::CONFIG_KEY, null);
            if (is_array($cfg)) return array_merge(self::defaultConfig(), $cfg);
        }
        return self::defaultConfig();
    }

    public static function save(Collection $col, array $acl): void
    {
        if (method_exists($col, 'setCustomConfig')) {
            $col->setCustomConfig(self::CONFIG_KEY, $acl);
            $col->saveConfiguration();
        }
    }

    public static function getJwtSecret(): string
    {
        return $_ENV['JWT_SECRET'] ?? $_ENV['ENCRYPTION_KEY'] ?? 'bangrondb-admin-secret-change-me-32chars!';
    }

    public static function userFromRequest(array $reqHeaders, array $acl): array
    {
        // returns ['id'=>?, 'username'=>?, 'roles'=>[...]]
        $roles = self::userRoles($reqHeaders, $acl, $userPayload);
        return [
            'id' => $userPayload['sub'] ?? $userPayload['uid'] ?? null,
            'username' => $userPayload['username'] ?? $userPayload['email'] ?? $userPayload['name'] ?? null,
            'roles' => $roles,
            'payload' => $userPayload,
        ];
    }

    private static array $aclCache = [];
    private static int $aclCacheTtl = 60; // seconds

    public static function userRoles(array $reqHeaders, array $acl, ?array &$jwtPayload = null): array
    {
        $jwtPayload = [];
        // 0. SESSION (Web Admin Studio) – highest priority for browser
        if (class_exists(\\App\\Security\\SessionAuth::class)) {
            $sess = \App\Security\SessionAuth::user();
            if ($sess) {
                $jwtPayload = [
                    'sub' => $sess['_id'] ?? null,
                    'username' => $sess['username'] ?? null,
                    'email' => $sess['email'] ?? null,
                    'name' => $sess['name'] ?? $sess['username'] ?? null,
                    'role' => $sess['role'] ?? 'guest',
                    'roles' => $sess['roles'] ?? [$sess['role'] ?? 'guest'],
                    'type' => 'session',
                ];
                // return single role as array for BC
                if (!empty($sess['roles']) && is_array($sess['roles'])) return $sess['roles'];
                if (!empty($sess['role'])) return [$sess['role']];
            }
        }
        // 1. API Key
        $apiKey = $reqHeaders['x-api-key'] ?? $reqHeaders['X-Api-Key'] ?? $reqHeaders['X-API-KEY'] ?? null;
        if ($apiKey) {
            foreach ($acl['api_keys'] ?? [] as $k) {
                if (hash_equals($k['key'] ?? '', $apiKey)) {
                    return $k['roles'] ?? [$acl['default_role'] ?? 'guest'];
                }
            }
        }
        // 2. Explicit role header (dev / trusted proxy)
        $roleHeader = $reqHeaders['x-role'] ?? $reqHeaders['X-Role'] ?? $reqHeaders['x-user-role'] ?? $reqHeaders['X-User-Role'] ?? null;
        if ($roleHeader) {
            return array_values(array_filter(array_map('trim', explode(',', $roleHeader))));
        }
        // 3. JWT Bearer – verified + blacklist check
        $auth = $reqHeaders['authorization'] ?? $reqHeaders['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (stripos($auth, 'bearer ') === 0) {
            $token = trim(substr($auth, 7));
            $payload = Jwt::decode($token, self::getJwtSecret(), true);
            if ($payload) {
                // type must be access
                if (($payload['type'] ?? 'access') !== 'access') {
                    return [$acl['default_role'] ?? 'guest'];
                }
                // blacklist check
                $jti = $payload['jti'] ?? null;
                if ($jti) {
                    try {
                        // lazy client – use global Flight if available
                        if (class_exists('\\Flight') && \Flight::has('bangron')) {
                            $client = \Flight::bangron()->getClient();
                            if (\App\Security\TokenBlacklist::isRevoked($client, $jti)) {
                                return [$acl['default_role'] ?? 'guest'];
                            }
                        }
                    } catch (\Throwable $e) {}
                }
                $jwtPayload = $payload;
                if (isset($payload['roles']) && is_array($payload['roles'])) return $payload['roles'];
                if (isset($payload['role'])) return (array)$payload['role'];
                // fallback scope claim
                if (isset($payload['scope'])) {
                    return is_array($payload['scope']) ? $payload['scope'] : explode(' ', $payload['scope']);
                }
            }
        }
        return [$acl['default_role'] ?? 'guest'];
    }

    // permission cache wrapper for load()
    public static function loadCached(\BangronDB\Collection $col, string $dbName='', string $colName=''): array
    {
        $key = $dbName.'.'.$colName;
        $now = time();
        if ($key && isset(self::$aclCache[$key])) {
            $c = self::$aclCache[$key];
            if (($now - $c['t']) < self::$aclCacheTtl) {
                return $c['acl'];
            }
        }
        $acl = self::load($col);
        if ($key) {
            self::$aclCache[$key] = ['t'=>$now, 'acl'=>$acl];
        }
        return $acl;
    }

    public static function clearCache(?string $key=null): void
    {
        if($key) unset(self::$aclCache[$key]); else self::$aclCache=[];
    }

    public static function can(array $roles, string $action, array $acl): bool
    {
        if (empty($acl['enabled'])) return true;
        $roleDefs = $acl['roles'] ?? [];
        foreach ($roles as $r) {
            $perms = $roleDefs[$r] ?? [];
            if (class_exists(\\App\\Security\\PermissionRegistry::class)) {
                if (\\App\\Security\\PermissionRegistry::isAllowed($action, $perms)) return true;
            } else {
                if (in_array('*', $perms, true)) return true;
                if (in_array($action, $perms, true)) return true;
                $map = [
                    'find' => 'read', 'findOne' => 'read', 'count' => 'read',
                    'insert' => 'create', 'save' => 'create',
                    'update' => 'update',
                    'remove' => 'delete', 'delete' => 'delete',
                ];
                if (isset($map[$action]) && in_array($map[$action], $perms, true)) return true;
            }
        }
        return false;
    }

    /**
     * Field-level: support deny AND allowlist mode.
     * 
     * Config examples:
     *  "field_rules": {
     *    "editor": { "email": "deny", "salary": "deny" }
     *    "user": { "__mode": "allow", "name": "allow", "email": "allow" }
     *  }
     */
    public static function filterFields(array $document, array $roles, array $acl): array
    {
        $rules = $acl['field_rules'] ?? [];
        if (!$rules) return $document;

        // collect allow & deny per role, merge (deny wins)
        $allowSets = [];
        $deny = [];

        foreach ($roles as $r) {
            if (empty($rules[$r]) || !is_array($rules[$r])) continue;
            $roleRule = $rules[$r];
            $mode = $roleRule['__mode'] ?? 'deny';
            unset($roleRule['__mode']);
            if ($mode === 'allow') {
                $allowSets[] = array_keys(array_filter($roleRule, fn($v)=> $v==='allow' || $v===true));
            } else {
                // deny mode
                foreach ($roleRule as $field => $m) {
                    if ($m === 'deny' || $m === false || $m === '0') {
                        $deny[] = $field;
                    }
                }
            }
        }

        // if any allowlist present → intersection (union of allows across roles)
        if (!empty($allowSets)) {
            $allowed = array_unique(array_merge(...$allowSets));
            // always keep _id
            $allowed[] = '_id';
            $document = array_intersect_key($document, array_flip($allowed));
        }

        // apply deny (deny overrides allow)
        foreach (array_unique($deny) as $fieldPath) {
            if (array_key_exists($fieldPath, $document)) {
                unset($document[$fieldPath]);
                continue;
            }
            // dot notation
            if (str_contains($fieldPath, '.')) {
                $parts = explode('.', $fieldPath);
                $tmp =& $document;
                foreach ($parts as $i => $p) {
                    if (!is_array($tmp) || !array_key_exists($p, $tmp)) { break; }
                    if ($i === count($parts)-1) { unset($tmp[$p]); break; }
                    $tmp =& $tmp[$p];
                }
                unset($tmp);
            }
        }
        return $document;
    }

    public static function rowFilter(array $roles, array $acl, ?array $userPayload = null): array
    {
        $filters = [];
        foreach ($roles as $r) {
            if (!empty($acl['row_filters'][$r]) && is_array($acl['row_filters'][$r])) {
                $f = $acl['row_filters'][$r];
                if ($userPayload) {
                    $f = self::substituteUserVariables($f, $userPayload);
                }
                $filters[] = $f;
            }
        }
        if (count($filters) === 0) return [];
        if (count($filters) === 1) return $filters[0];
        return ['$and' => $filters];
    }

    // Replace {{user.sub}}, {{user.username}}, {{user.email}}, {{user.role}}, etc.
    public static function substituteUserVariables(mixed $filter, array $user): mixed
    {
        if (is_array($filter)) {
            $out = [];
            foreach ($filter as $k => $v) {
                $out[$k] = self::substituteUserVariables($v, $user);
            }
            return $out;
        }
        if (is_string($filter)) {
            $map = [
                '{{user.sub}}' => $user['sub'] ?? $user['id'] ?? '',
                '{{user.id}}' => $user['sub'] ?? $user['id'] ?? '',
                '{{user.uid}}' => $user['sub'] ?? $user['uid'] ?? '',
                '{{user.username}}' => $user['username'] ?? '',
                '{{user.email}}' => $user['email'] ?? '',
                '{{user.name}}' => $user['name'] ?? $user['username'] ?? '',
                '{{user.role}}' => $user['role'] ?? (is_array($user['roles'] ?? null) ? ($user['roles'][0] ?? '') : ($user['roles'] ?? '')),
            ];
            // support array roles to string
            if (str_contains($filter, '{{user.roles}}')) {
                $map['{{user.roles}}'] = is_array($user['roles'] ?? null) ? implode(',', $user['roles']) : ($user['roles'] ?? '');
            }
            $result = strtr($filter, $map);
            // also support ${user.sub} style
            foreach ($map as $k2 => $v2) {
                $alt = str_replace(['{{','}}'], ['${','}'], $k2);
                $result = str_replace($alt, (string)$v2, $result);
            }
            return $result;
        }
        return $filter;
    }

    public static function mergeCriteria(array $userFilter, array $aclFilter): array
    {
        if (empty($aclFilter)) return $userFilter;
        if (empty($userFilter)) return $aclFilter;
        return ['$and' => [$userFilter, $aclFilter]];
    }

    // Helper to get a cleaned user array for audit
    public static function auditUser(array $reqHeaders, array $acl): array
    {
        $payload = [];
        $roles = self::userRoles($reqHeaders, $acl, $payload);
        return [
            'id' => $payload['sub'] ?? $payload['uid'] ?? null,
            'username' => $payload['username'] ?? $payload['email'] ?? $payload['name'] ?? 'anonymous',
            'roles' => $roles,
        ];
    }
}
