<?php

$loader = require dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$db = Grocery\Base::connect('pgsql://admin:test@localhost:5432/test#pdo');

$list = array(
  'my_id' => 'primary_key',
  'value' => 'string',
); // model

isset($db['list']) OR $db['list'] = $list;

$list = $db['list'];

$last_id = $list->insert(array('value' => 'xD'), 'my_id');

echo 'LAST_ID: ', $last_id, "\n";
echo 'COUNT: ', $list->count(), "\n";
