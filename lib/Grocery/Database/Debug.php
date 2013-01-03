<?php

namespace Grocery\Database;

class Debug
{

  private $logger = NULL;

  private $queries = array(
            'sql' => array(),
            'ms' => array(),
          );



  public function __construct($callback)
  {
    $this->logger = $callback;
  }


  public function debug($test)
  {
    is_callable($this->logger) && call_user_func($this->logger, $test);
  }

  public function last($key = FALSE)
  {
    return ! empty($this->queries[$key]) ? end($this->queries[$key]) : FALSE;
  }

  public function start($sql)
  {
    $this->debug(" => $sql");
    $this->queries['sql'] []= $sql;
    $this->queries['ms'] []= microtime(TRUE);
  }

  public function stop()
  {
    $test =& $this->queries['ms'];

    $old = array_pop($test);
    $new = microtime(TRUE) - $old;

    $this->debug(" <= $new");
    $test []= $new;
  }

  public function all()
  {
    return $this->queries;
  }

}
