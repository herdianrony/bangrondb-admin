<?php
require __DIR__.'/vendor/autoload.php';
use BangronDB\Client;

$client = new Client(__DIR__.'/storage/data');

// --- AUTH DB ---
$client->createDB('auth');
$client->createCollection('auth','users');
$client->createCollection('auth','roles');
$client->createCollection('auth','permissions');

// Seed Permissions
$permissions = $client->selectCollection('auth','permissions');
$permissions->remove([]);
$permSeeds = [
    ['_id'=>'read', 'name'=>'read', 'label'=>'Read', 'description'=>'Can read data'],
    ['_id'=>'create', 'name'=>'create', 'label'=>'Create', 'description'=>'Can create new records'],
    ['_id'=>'update', 'name'=>'update', 'label'=>'Update', 'description'=>'Can update existing records'],
    ['_id'=>'delete', 'name'=>'delete', 'label'=>'Delete', 'description'=>'Can delete records'],
    ['_id'=>'manage_schema', 'name'=>'manage_schema', 'label'=>'Manage Schema', 'description'=>'Can modify collection schemas'],
    ['_id'=>'manage_acl', 'name'=>'manage_acl', 'label'=>'Manage ACL', 'description'=>'Can manage access control'],
    ['_id'=>'manage_users', 'name'=>'manage_users', 'label'=>'Manage Users', 'description'=>'Can manage users and roles'],
];
foreach($permSeeds as $p){ try{ $permissions->save($p); }catch(Throwable $e){} }

// Seed Roles
$roles = $client->selectCollection('auth','roles');
$roles->remove([]);
$roles->setSchema([
  'name' => ['required'=>true,'type'=>'string','unique'=>true],
  'label' => ['type'=>'string'],
  'permissions' => ['type'=>'array'],
  'is_system' => ['type'=>'bool'],
]);
$roles->saveConfiguration();

$roleSeeds = [
  ['_id'=>'superadmin','name'=>'superadmin','label'=>'Super Administrator','permissions'=>['*'],'is_system'=>true,'description'=>'Full access all databases & collections'],
  ['_id'=>'admin','name'=>'admin','label'=>'Administrator','permissions'=>['read','create','update','delete','manage_schema','manage_acl'],'is_system'=>true],
  ['_id'=>'editor','name'=>'editor','label'=>'Editor','permissions'=>['read','create','update'],'is_system'=>true],
  ['_id'=>'user','name'=>'user','label'=>'User','permissions'=>['read'],'is_system'=>true],
  ['_id'=>'guest','name'=>'guest','label'=>'Guest','permissions'=>[],'is_system'=>true],
];
foreach($roleSeeds as $r){ try{ $roles->save($r); }catch(Throwable $e){} }

$users = $client->selectCollection('auth','users');
$users->setSchema([
  'username'=>['required'=>true,'type'=>'string','unique'=>true,'min'=>3],
  'email'=>['type'=>'string','unique'=>true],
  'password_hash'=>['required'=>true,'type'=>'string'],
  'roles'=>['type'=>'array'],
  'active'=>['type'=>'bool'],
]);
$users->saveConfiguration();

// superadmin
if(!$users->findOne(['username'=>'superadmin'])){
    $users->insert([
        '_id'=>'usr_superadmin',
        'username'=>'superadmin',
        'email'=>'superadmin@bangrondb.local',
        'name'=>'Super Admin',
        'password_hash'=>password_hash('SuperAdmin123!', PASSWORD_ARGON2ID),
        'roles'=>['superadmin'],
        'active'=>true,
        'must_change_password'=>true,
        'created_at'=>date('c'),
    ]);
    echo "Superadmin created: superadmin / SuperAdmin123!\n";
} else {
    echo "Superadmin already exists.\n";
}

// --- sample app ACL setup ---
$client->createDB('app');
if(!$client->collectionExists('app','users')) $client->createCollection('app','users');
$appUsers = $client->selectCollection('app','users');
$appUsers->setCustomConfig('acl', [
  'enabled' => true,
  'default_role' => 'guest',
  'roles' => [
    'superadmin'=>['*'],
    'admin'=>['read','create','update','delete','manage_schema','manage_acl'],
    'editor'=>['read','create','update'],
    'user'=>['read'],
    'guest'=>[]
  ],
  'field_rules' => [
    'user' => ['password_hash'=>'deny','email'=>'deny'],
    'editor' => ['password_hash'=>'deny'],
    // example allowlist:
    // 'guest' => ['__mode'=>'allow','username'=>'allow']
  ],
  'row_filters' => [
    'user' => ['active'=>true],
  ],
  'api_keys' => []
]);
$appUsers->saveConfiguration();

echo "Roles seeded: ". $roles->count() ."\n";
echo "Users in auth.users: ". $users->count() ."\n";
echo "ACL enabled on app.users\n";
echo "\nLogin:\n  POST /api/auth/login\n  {\"username\":\"superadmin\",\"password\":\"SuperAdmin123!\"}\n";
