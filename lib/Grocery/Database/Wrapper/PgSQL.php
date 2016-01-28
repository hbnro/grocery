<?php

namespace Grocery\Database\Wrapper;

class PgSQL
{

  private $bm = NULL;
  private $res = NULL;
  private $vval = NULL;

  public static function factory(array $params, $debugger)
  {
    $obj = new static;

    $conn  = "dbname=" . trim($params['path'], '/');
    $conn .= " host={$params['host']} user=$params[user]";
    $conn .= !empty($params['port'])? " port=$params[port]": '';
    $conn .= !empty($params['pass'])? " password=$params[pass]": '';

    $obj->bm = $debugger;
    $obj->res = pg_connect($conn);

    return $obj;
  }

  public function stats()
  {
    return $this->bm->all();
  }

  public function version()
  {
    if ($this->vval === NULL) {
      $this->vval = pg_fetch_row(pg_query($this->res, 'SELECT version()'), 0);
    }

    return $this->vval;
  }

  public function execute($sql)
  {
    $this->bm->start($sql);
    $out = @pg_query($this->res, $sql);
    $this->bm->stop();

    return $out;
  }

  public function real_escape($test)
  {
    return pg_escape_string($this->res, $test);
  }

  public function has_error()
  {
    return pg_last_error($this->res);
  }

  public function fetch_result($res)
  {
    $out = pg_fetch_result($res, 0);

    ($out === 'f') && $out = FALSE;
    ($out === 't') && $out = TRUE;

    return $out;
  }

  public function fetch_assoc($res)
  {
    return $this->fixate_bools(pg_fetch_assoc($res));
  }

  public function count_rows($res)
  {
    return pg_num_rows($res);
  }

  public function affected_rows($res)
  {
    return pg_affected_rows($res);
  }

  public function last_inserted_id($res)
  {
    return pg_fetch_result($res, 0);
  }

  public function fixate_bools($set)
  {
    if (is_array($set)) {
      foreach ($set as $key => $val) {
        ($val === 'f') && $set[$key] = FALSE;
        ($val === 't') && $set[$key] = TRUE;
      }
    }

    return $set;
  }
}
