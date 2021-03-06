<?php

namespace Grocery\Database\SQL;

class Scheme extends Query
{

  public function begin()
  {
    return (boolean) $this->begin_transaction();
  }

  public function commit()
  {
    return (boolean) $this->commit_transaction();
  }

  public function rollback()
  {
    return (boolean) $this->rollback_transaction();
  }

  public function create($name, array $columns = array())
  {
    return $this->query($this->build_table($name, $columns));
  }

  public function drop($table)
  {
    return $this->execute('DROP TABLE ' . $this->quote_string($table));
  }

  public function import($from, $raw = FALSE)
  {
    ob_start();

    $old  = include $from;
    $test = ob_get_clean();

    if (!is_array($old)) {
      if ($raw) {
        return array_map(array($this, 'execute'), \Grocery\Helpers::sql_split($test));
      }

      return FALSE;
    }

    foreach ((array) $old as $key => $val) {
      if (!empty($val['scheme'])) {
        $this->build_table($key, (array) $val['scheme']);
      }

      if (!empty($val['data'])) {
        foreach ((array) $val['data'] as $one) {
          $this->insert($key, $one);
        }
      }
    }
  }

  public function export($mask = '*', $data = FALSE, $raw = FALSE)
  {
    $out = array();

    foreach ($this->tables($mask) as $one) {
      foreach ($this->columns($one) as $key => $val) {
        $out[$one]['scheme'][$key] = array(
          $val['type'],
          $val['length'],
          $val['default'],
        );
      }

      if ($data) {
        $result = $this->select($one, '*');
        $out[$one]['data'] = $this->fetch_all($result, TRUE);
      }
    }

    if ($raw) {
      $old = array();

      foreach ($out as $key => $val) {
        $old []= $this->build_table($key, $val['scheme']) . ';';

        if (!empty($val['data'])) {
          foreach ((array) $val['data'] as $one) {
            $keys   = $this->build_fields($key);
            $values = $this->build_values($one, TRUE);

            $old [] = sprintf("INSERT INTO\n%s\n%s;", $keys, $values);
          }
        }
      }

      $text = join("\n", $old);
    } else {
      $code = var_export($out, TRUE);
      $text = '<' . "?php return $code;";
    }

    return $text;
  }

  public function tables($filter = '*')
  {
    $out  = array();
    $test = $this->fetch_tables();

    if ($filter === '*') {
      return $test;
    }

    foreach ($test as $one) {
      if (fnmatch($filter, $one)) {
        $out []= $one;
      }
    }

    return $out;
  }

  public function columns($of)
  {
    $out = array();
    $test = $this->fetch_columns($of);

    foreach ($test as $key => $val) {
      $default     = !empty(static::$types[$val['type']]) ? static::$types[$val['type']] : $val['type'];
      $val['type'] = strtolower($default);
      $out[$key]  = $val;
    }

    return $out;
  }

  public function indexes($of)
  {
    return $this->fetch_indexes($of);
  }

}
