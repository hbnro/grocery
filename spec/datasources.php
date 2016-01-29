<?php

return function(\Closure $lambda) {
  $datasources = array_filter(array(
    'sqlite::memory:',
    'sqlite::memory:#pdo',
    'mysqli://root@localhost/grocery',
    'mysqli://root@localhost/grocery#pdo',
    (getenv('CI') && !defined('HHVM_VERSION')) ? 'pgsql://postgres@localhost/grocery' : '',
    (getenv('CI') && !defined('HHVM_VERSION')) ? 'pgsql://postgres@localhost/grocery#pdo' : '',
  ));

  array_map(function($conn) use ($lambda) {
    try {
      $db = \Grocery\Base::connect($conn);
      $lambda($db, $conn);
    } catch (\Exception $e) {
      echo $e;
      exit(1);
    }
  }, $datasources);
};
