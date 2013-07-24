<?php

namespace Grocery\Database\SQL;

class Query extends Dump
{

  public function select($table, $fields = '*', array $where = array(), array $options = array())
  {
    return new \Grocery\Handle\Result($this->query($this->build_select($table, $fields, $where, $options)), $this);
  }

  public function insert($table, array $values, $column = NULL)
  {
    return $this->inserted($this->query($this->build_insert($table, $values, $column)));
  }

  public function delete($table, array $where = array(), $limit = 0, $column = NULL)
  {
    return $this->affected($this->query($this->build_delete($table, $where, $limit, $column)));
  }

  public function update($table, array $fields, array $where = array(), $limit = 0, $column = NULL)
  {
    return $this->affected($this->query($this->build_update($table, $fields, $where, $limit, $column)));
  }

  public function prepare($sql, array $vars = array())
  {
    if (\Grocery\Helpers::is_assoc($vars)) {
      $sql = strtr($sql, $this->fixate_value($vars, TRUE));
    } else {
      $args = $this->fixate_value($vars, TRUE);
      $sql  = preg_replace_callback('/((?<!\\\)\?)/', function ()
        use ($args) {
          return array_shift($args);
        }, $sql);
    }

    return $sql;
  }

  public function query($sql, $repl = array())
  {
    if (func_num_args() > 1) {
      $args = func_num_args() > 2 ? func_get_args() : (array) $repl;
      $sql  = $this->prepare($sql, $args);
    }

    $out = $this->execute($this->query_repare($sql));

    if ($message = $this->has_error()) {
      throw new \Exception("Database failure '$message'\n---\n$sql\n---");
    }

    return $out;
  }

  public function result($test, $default = FALSE)
  {
    if (is_string($test)) {
      $test = $this->query($test);
    }

    return $this->fetch_result($test) ?: $default;
  }

  public function fetch_all($result)
  {
    $out = array();

    if (is_string($result)) {
      $args     = func_get_args();
      $callback = strpos($result, ' ') ? 'query' : 'select';
      $result   = call_user_func_array(array($this, $callback), $args);
    }

    while ($row = $this->fetch($result)) {
      $out []= $row;
    }

    return $out;
  }

  public function fetch($result)
  {
    return ($tmp = $this->fetch_assoc($result)) ? new \Grocery\Handle\Record($tmp, $this) : FALSE;
  }

  public function numrows($result)
  {
    return $this->count_rows($result);
  }

  public function affected($result)
  {
    return $this->affected_rows($result);
  }

  public function inserted($result)
  {
    return $this->last_inserted_id($result);
  }

}
