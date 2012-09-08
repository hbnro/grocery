<?php

namespace Grocery\Database;

class Debug
{

  private $queries = array(
            'sql' => array(),
            'ms' => array(),
          );



  public function last($key = FALSE)
  {
    return ! empty($this->queries[$key]) ? end($this->queries[$key]) : FALSE;
  }

  public function start($sql)
  {
    $this->queries['sql'] []= $sql;
    $this->queries['ms'] []= microtime(TRUE);
  }

  public function stop()
  {
    $test =& $this->queries['ms'];

    $old = array_pop($test);
    $new = microtime(TRUE) - $old;

    $test []= $new;
  }

  public function all()
  {
    return $this->queries;
  }

}
