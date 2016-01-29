<?php

return function(\Closure $lambda) {
  $datasources = array_filter(array(
    'sqlite::memory:',
    'mysqli://root@localhost/grocery',
    getenv('CI') && !defined('HHVM_VERSION') ? 'pgsql://postgres@localhost/grocery' : ''
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
