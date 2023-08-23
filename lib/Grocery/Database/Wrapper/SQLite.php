<?php

namespace Grocery\Database\Wrapper;

#[AllowDynamicProperties]
class SQLite
{

  private $bm = NULL;
  private $res = NULL;

  public static function factory(array $params, $debugger)
  {
    $db_file = $params['host'] . $params['path'];

    if (!is_file($db_file) && ($db_file <> ':memory:')) {
      throw new \Exception("The file '$db_file' does not exists");
    }

    $obj = new static;
    $obj->bm = $debugger;
    $obj->res = new \SQLite3($db_file);

    $obj->res->createfunction('concat', function () {
        return implode(func_get_args(), '');
      });

    $obj->res->createfunction('md5rev', function ($str) {
        return strrev(md5($str));
      }, 1);

    $obj->res->createfunction('mod', function ($a, $b) {
        return $a % $b;
      }, 2);

    $obj->res->createfunction('md5', function ($str) {
        return md5($str);
      }, 1);

    $obj->res->createfunction('now', function () {
        return time();
      }, 0);

    return $obj;
  }

  public function now()
  {
    return \Grocery\Base::plain('CURRENT_TIMESTAMP');
  }

  public function stats()
  {
    return $this->bm->all();
  }

  public function version()
  {
    $test = $this->res->version();

    return $test['versionString'];
  }

  public function execute($sql)
  {
    $this->bm->start($sql);
    $out = @$this->res->query($sql);
    $this->bm->stop();

    return $out;
  }

  public function real_escape($test)
  {
    return str_replace("'", "''", stripslashes($test));
  }

  public function has_error()
  {
    return $this->res->lastErrorCode() ? $this->res->lastErrorMsg() : FALSE;
  }

  public function fetch_result($res)
  {
    return ($tmp = $this->fetch_assoc($res)) ? array_shift($tmp) : FALSE;
  }

  public function fetch_assoc($res)
  {
    return $res ? $res->fetchArray(SQLITE3_ASSOC) : FALSE;
  }

  public function count_rows($res)
  {
    if ($tmp = $this->bm->last('sql')) {
      return $this->fetch_result($this->execute("SELECT COUNT(*) FROM ($tmp)"));
    }
  }

  public function affected_rows()
  {
    return $this->res->changes();
  }

  public function last_inserted_id()
  {
    return $this->res->lastInsertRowID();
  }

}
