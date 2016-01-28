<?php

return function(\Closure $lambda) {
  $datasources = array(
    'sqlite::memory:'
  );

  array_map(function($conn) use ($lambda) {
    $lambda(\Grocery\Base::connect($conn), $conn);
  }, $datasources);
};
