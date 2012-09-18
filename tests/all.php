<?php

$loader = require dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$fmtsize = function ($bytes) {
    $test = array('', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');
    $key = 0;

    while ($bytes >= 1024) {
      $bytes = $bytes / 1024;
      $key += 1;
    }

    return floor($bytes).preg_replace('/^iB/', 'Bi', "$test[$key]iB");
  };



// preconfigure
Grocery\Config::set('unserialize', 'reset');


// required
is_file($db_file = __DIR__.DIRECTORY_SEPARATOR.'db.sqlite') OR touch($db_file);


// tests
foreach (array(
  'sqlite::memory:',
  'sqlite:'.$db_file,
  'sqlite:'.$db_file.'#pdo',
  #'mysqli://root:test@localhost/test',
  #'mysqli://root:test@localhost/test#pdo',
  #'pgsql://postgres:test@localhost/test',
  #'pgsql://postgres:test@localhost/test#pdo',
) as $test) {

  echo "\n\n=== New connection: $test\n";

  // connection singletons
  $db = Grocery\Base::connect($test);

  $col = array('str' => array('string'), 'id' => array('primary_key'));
  $set = array('foo', 'bar');

  $top = rand(3, 7);
  $max = sizeof($set) * 10;

  $db->reset();

  // table operations
  echo "\nCreate table: ";
  foreach ($set as $tbl) { $db->create($tbl, $col); }

  echo sizeof($db->tables()) === sizeof($set) ? 'OK' : 'FAIL';

  echo "\nRename table: ";
  $db->rename('bar', 'candy');
  $db->rename('foo', 'bar');
  $db->rename('candy', 'foo');
  echo ! in_array('candy', $db->tables()) && in_array('bar', $db->tables()) ? 'OK' : 'FAIL';

  echo "\nDrop table: ";
  $db->create('nothing', $col);
  echo $db->drop('nothing') ? 'OK' : 'FAIL';



  // column operations
  echo "\n\nAdd column: ";
  $db->add_column('foo', 'does', 'integer');
  echo array_key_exists('does', $db->columns('foo')) ? 'OK' : 'FAIL';

  echo "\nRename column: ";
  $db->rename_column('foo', 'does', 'nothing');
  echo array_key_exists('nothing', $db->columns('foo')) ? 'OK' : 'FAIL';

  echo "\nChange column: ";
  $db->change_column('foo', 'nothing', 'string');
  $tmp = $db->columns('foo');
  echo $tmp['nothing']['type'] === 'string' ? 'OK' : 'FAIL';

  echo "\nDelete column: ";
  $db->remove_column('foo', 'nothing');
  echo ! array_key_exists('nothing', $db->columns('foo')) && array_key_exists('str', $db->columns('foo')) ? 'OK' : 'FAIL';



  // index operations
  echo "\n\nAdd index: ";
  $db->add_index('bar', 'fuuuu', array('str'));
  $db->add_index('bar', 'my_str', array('str'));
  echo array_key_exists('my_str', $db->indexes('bar')) ? 'OK' : 'FAIL';


  echo "\nRemove index: ";
  $db->remove_index('bar', 'my_str');
  echo ! array_key_exists('my_str', $db->indexes('bar')) && array_key_exists('fuuuu', $db->indexes('bar')) ? 'OK' : 'FAIL';


  // all operations
  echo "\n\nCRUD: ";

  for ($i = 0; $i < $max; $i += 1) {
    $key = array_rand($set);
    $tbl = $set[$key];

    $val['str'] = str_repeat(md5(mt_rand()), $top);
    $db->insert($tbl, $val); // CREATE
  }


  $tmp = array();
  foreach ($db->tables() as $i => $one) {
    $tmp[$one] = $db->select($one)->fetch_all($i % 2); // READ
  }



  $ok = 0;

  foreach ($tmp as $tbl => $old) {
    $up = 0;
    $rm = 0;
    $del = array();
    $min = rand(1, ceil(sizeof($old) / rand(2, 9)));
    $all = $db->select($tbl, '*', array(), array('limit' => $min, 'order' => 'random'));

    while ($one = $all->fetch()) {
      $one->str = 'OK';
      $del []= $one->id;
      $up += (int) !! $db->update($tbl, $one->to_a(), array('id' => end($del))); // UPDATE
    }

    $cur = $db->result("SELECT COUNT(*) FROM $tbl WHERE str = 'OK'");

    $cur == $up ? $ok += 1 : 'FAIL';

    $diff = $db->delete($tbl, array('id' => $del)); // DELETE
    $now = $db->select($tbl, 'COUNT(*)', array('str !' => 'OK'))->result();
    $test = sizeof($old) - $cur;
    $sum = $diff + $test;
    $max = sizeof($old);

    $sum == $max ? $ok += 1 : 'FAIL';
  }

  echo ($ok / 2) === sizeof($set) ? 'OK' : 'FAIL';
  echo "\n\n";


  foreach ($db->to_a() as $i => $one) { echo "TABLE: $i => " . json_encode($one) . "\n"; }
  foreach ($db as $i => $one) { $one->drop(); }

  echo "\n";

  $db->list->create(array('title' => 'string', 'id' => 'primary_key'));

  for ($i = 0; $i < 10; $i += 1) {
    $db->list->insert(array('title' => 'Hello World!'));
  }

  $db->list->update(array('title' => 'FUUUUUUUUU!!!'), array('id' => array(2,4,6,8)));
  $db->list->delete(array('id' => array(1,3,5,7,9)));

  echo json_encode($db['list']->select()->fetch_all());
  echo "\n";

  echo json_encode($db['list']->columns());
  echo "\n";

  echo json_encode($db->tables());
  echo "\n";



  $post = array(
    'title' => 'string',
    'body' => 'text',
  ); // model

  isset($db['post']) OR $db['post'] = $post;

  $post = $db['post'];

  echo "\nExport:\n\n";
  echo preg_replace('/^/m', '  ', $db);

  for ($i = 0; $i < $top; $i += 1) {
    $post->insert(array('title' => md5(uniqid(''))));
  }


  echo "\n\nFinders: ";
  echo (count($post) == $top) && ($post->count() == $top) ? 'OK' : 'FAIL';

  $post->limit(1)->each(function ($row) {
    echo "\n\nSample: $row->title";
  });



  echo "\n\nCount: ";
  echo $post->count();

  echo "\nPicks: ";

  $c = 13;

  while ($c -= 1) {
    echo $post->select('title', array(), array('order' => 'random'))->fetch()->title . "\n       ";
  }

  $post->delete();
  echo "\nClear: ";
  echo ! $post->select('COUNT(*)')->result() ? 'OK' : 'FAIL';

  echo "\n\n";




  echo "\n=== Benchmarks\n";

  $tmp = $db->stats();
  $len = $fmtsize(strlen(join('', $tmp['sql'])));
  $all = round(array_sum($tmp['ms']), 4);
  $max = sizeof($tmp['ms']);

  echo "\n$all ms, {$max}x$top $len";
  echo "\n\n";
}
