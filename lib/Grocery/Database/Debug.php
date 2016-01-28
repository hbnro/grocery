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

  public function last($key = FALSE)
  {
    return !empty($this->queries[$key]) ? end($this->queries[$key]) : FALSE;
  }

  public function start($sql)
  {
    $this->queries['sql'] []= $sql;
    $this->queries['ms'] []= microtime(TRUE);
  }

  public function stop()
  {
    if (is_callable($this->logger)) {
      call_user_func($this->logger, $this->last('sql'), microtime(TRUE) - $this->last('ms'));
    }
  }

  public function all()
  {
    return $this->queries;
  }

}
