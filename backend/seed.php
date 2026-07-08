<?php
require __DIR__.'/vendor/autoload.php';
use BangronDB\Client;

$client = new Client(__DIR__.'/storage/data');
$client->createDB('app');
$client->createCollection('app','users');
$client->createCollection('app','posts');

$users = $client->selectCollection('app','users');
$users->setSchema([
  'username'=>['required'=>true,'type'=>'string','min'=>3],
  'email'=>['required'=>true,'type'=>'string','unique'=>true],
  'role'=>['type'=>'string','enum'=>['admin','editor','user']]
]);
$users->saveConfiguration();

$users->remove([]);
$users->insert([
  ['username'=>'herdian','email'=>'herdian@example.com','role'=>'admin','age'=>30],
  ['username'=>'alice','email'=>'alice@example.com','role'=>'editor','age'=>25],
  ['username'=>'bob','email'=>'bob@example.com','role'=>'user','age'=>22],
]);

$posts = $client->selectCollection('app','posts');
$posts->remove([]);
$u1 = $users->findOne(['username'=>'herdian']);
$posts->insert([
  ['title'=>'Hello BangronDB','author_id'=>$u1['_id'],'tags'=>['php','sqlite']],
  ['title'=>'Encryption test','author_id'=>$u1['_id'],'tags'=>['security']],
]);

echo "Seeded app.users (3) + app.posts (2)\n";
