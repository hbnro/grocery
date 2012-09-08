<?php

namespace Grocery\Database\SQL;

class Dump extends Base
{

  protected static $regex = array(
                    'delete' => '/^\s*DELETE\s+FROM\s+(\S+)\s*$/is',
                    'limit' => '/\s+LIMIT\s+(\d+)(?:\s*(?:,|\s+TO\s+)\s*(\d+))?\s*$/i',
                  );



  protected function build_field($type, $length = 0, $default = NULL)
  {
    $tmp = static::$raw;

    if (empty($type)) {
      return FALSE;
    } else {
      $test = is_string($type) && ! empty($tmp[$type]) ? $tmp[$type] : $type;
    }

    if (\Grocery\Helpers::is_assoc($test)) {
      $test = array_merge(compact('length', 'default'), $test);

      $type    = ! empty($test['type']) ? $test['type'] : $type;
      $length  = ! empty($test['length']) ? $test['length'] : $length;
      $default = ! empty($test['default']) ? $test['default'] : $default;
    } elseif (is_array($test)) {
      @list($type, $length, $default) = $test;
    } elseif ($test !== $type) {
      return $test;
    }

    if ( ! empty($tmp[$type])) {
      if (is_string($tmp[$type])) {
        return $tmp[$type];
      }

      $length OR $length = ! empty($tmp[$type]['length']) ? $tmp[$type]['length'] : 0;
      $type = $tmp[$type]['type'];
    }

    $type  = strtoupper($type);
    $type .= $length > 0 ? sprintf('(%d)', $length) : '';
    $type .= $default ? ' NOT NULL' : '';

    if ( ! ($default === NULL)) {
      $type .= ' DEFAULT ' . ($default === NULL ? 'NULL' : $this->fixate_string($default));
    }

    return $type;
  }

  protected function build_table($name, array $columns = array())
  {
    $name = $this->quote_string($name);
    $sql  = "CREATE TABLE $name";


    if ($columns) {
      $sql .= "\n(\n";
      foreach ($columns as $key => $value) {
        $sql .= sprintf(" %s %s,\n", $this->quote_string($key), $this->build_field($value));
      }
      $sql = rtrim($sql, "\n,") . "\n)";
    }

    return $sql;
  }

  protected function build_select($table, $fields = '*', array $where = array(), array $options = array())
  {
    $sql  = "SELECT\n" . $this->build_fields($fields);
    $sql .= "\nFROM\n" . $this->build_fields($table);

    if ( ! empty($where)) {
      $sql .= "\nWHERE\n" . $this->build_where($where);
    }

    if ( ! empty($options['group'])) {
      $sql .= "\nGROUP BY";

      if (is_array($options['group'])) {
        $sql .= "\n" . join(', ', array_map(array('sql', 'names'), $options['group']));
      } else {
        $sql .= "\n" . $this->protect_names($options['group']);
      }
    }

    if ( ! empty($options['order'])) {
      $inc  = 0;
      $sql .= "\nORDER BY";

      foreach ((array) $options['order'] as $one => $set) {
        if (($inc += 1) > 1) {
          $sql .= ', ';
        }

        if (is_numeric($one)) {
          $sql .= "\n";
          $sql .= $set === 'RANDOM' ? static::$random : $this->protect_names($set[0]) . " $set[1]";
          continue;
        }

        $one  = $this->protect_names($one);
        $sql .= "\n$one $set";
      }
    }

    $limit  = ! empty($options['limit']) ? $options['limit'] : 0;
    $offset = ! empty($options['offset']) ? $options['offset'] : 0;

    if ($limit > 0) {
      $sql .= "\nLIMIT " . ($offset > 0 ? "$offset," : '') . $limit;
    }

    return $sql;
  }

  protected function build_insert($table, array $values)
  {
    $sql  = "INSERT INTO\n" . $this->build_fields($table);
    $sql .= $this->build_values($values, TRUE);

    return $sql;
  }

  protected function build_delete($table, array $where = array(), $limit = 0)
  {
    $sql = "DELETE FROM\n" . $this->build_fields($table);

    if ( ! empty($where)) {
      $sql .= "\nWHERE\n" . $this->build_where($where);
    }
    $sql .= $limit > 0 ? "\nLIMIT $limit" : '';

    return $sql;
  }

  protected function build_update($table, array $fields, array $where  = array(), $limit = 0)
  {
    $sql  = "UPDATE\n" . $this->build_fields($table);
    $sql .= "\nSET\n" . $this->build_values($fields, FALSE);
    $sql .= "\nWHERE\n" . $this->build_where($where);
    $sql .= $limit > 0 ? "\nLIMIT {$limit}" : '';

    return $sql;
  }

  protected function build_fields($values)
  {
    $sql = array();

    foreach ((array) $values as $key => $val) {
      if (strlen(trim($val)) == 0) {
        continue;
      } elseif (is_numeric($key)) {
        $sql []= ' ' . $this->protect_names($val);
        continue;
      }

      $sql []= ' ' . $this->protect_names($key) . ' AS ' . $this->quote_string($val);
    }

    return join(",\n", $sql);
  }

  protected function build_values($fields, $insert = FALSE)
  {
    $sql    = array();
    $fields = (array) $fields;

    if ($insert) {
      $cols = array();

      foreach (array_keys($fields) as $one) {
        $cols []= $this->quote_string($one);
      }

      $sql []= '(' . join(', ', $cols) . ')';
      $sql []= "\nVALUES(";
    }


    $out   = array();
    $count = 0;
    $total = sizeof($fields);

    foreach ($fields as $key => $val) {
      if (is_numeric($key)) {
        $out []= $val;
      } else {
        $val = $this->fixate_string($val, TRUE);
        $val = is_numeric($val) ? $val : $val;

        if ($insert) {
          $out []= $val;
        } else {
          $out []= $this->quote_string($key) . " = $val";
        }
      }
    }

    $sql []= join(",\n", $out);

    if ($insert) {
      $sql []= ')';
    }

    return join('', $sql);
  }

  protected function build_where($test, $operator = 'AND')
  {
    $sql      = array();
    $operator = strtoupper($operator);

    foreach ($test as $key => $val) {
      if (is_numeric($key)) {
        if ( ! \Grocery\Helpers::is_assoc($val)) {
          $raw = array_shift($val);
          if ($val && strpos($raw, '?')) {
            $sql []= $this->prepare($raw, $val);
          } else {
            array_unshift($val, $raw) && $sql []= join("\n", $val);
          }
        } else {
          $sql []= is_array($val) ? $this->build_where($val, $operator) : $val;
        }
      } elseif (\Grocery\Helpers::is_keyword($key)) {
        $sql []= '(' . trim($this->build_where($val, strtoupper($key))) . ')';
      } elseif (preg_match('/_(?:and|or)_/i', $key, $match)) {
        $sub = array();
        foreach (explode($match[0], $key) as $one) {
          $sub[$one] = $val;
        }
        $sql []= '(' . trim($this->build_where($sub, strtoupper(trim($match[0], '_')))) . ')';
      } elseif (preg_match('/^(.+?)(?:\s+(!=?|[<>]=?|<>|NOT|R?LIKE)\s*)?$/', $key, $match)) {
        $sub = '';
        $key = $this->protect_names($match[1]);

        if ($val === NULL) {
          $sub = 'IS NULL';
        } else {
          $val = $this->fixate_string($val, FALSE);
          $sub = ! empty($match[2]) ? ($match[2] == '!' ? '!=' : $match[2]) : '=';
        }

        if (is_array($val) && (sizeof($val) > 1)) {
          $key  .= in_array($sub, array('!=', '<>')) ? ' NOT' : '';
          $sql []= " $key IN(" . join(', ', $val) . ")";
        } else {
          $val   = is_array($val) ? array_shift($val) : $val;
          $sql []= " $key $sub $val";
        }
      }
    }

    return join("\n$operator\n", $sql);
  }

  protected function query_repare($test)
  {
    if (method_exists($this, 'ensure_limit')) {
      $test = preg_replace_callback(self::$regex['limit'], array($this, 'ensure_limit'), $test);
    }

    $test = preg_replace(self::$regex['delete'], 'DELETE FROM \\1 WHERE 1=1', $test);

    return $test;
  }

  protected function protect_names($test)
  {
    $set = array_map('\\Grocery\\Helpers::trim', explode(',', $test));

    foreach ($set as $i => $val) {
      $test = array_map('\\Grocery\\Helpers::trim', explode('.', $val));
      $char = substr($this->quote_string('x'), 0, 1);

      foreach ($test as $key => $val) {
        if (preg_match('/^[\sa-zA-Z0-9_-]+$/', $val)) {
          $val = trim($val, $char);
          $val = $char . $val . $char;

          $test[$key] = $val;
        }
      }

      $set[$i] = join('.', $test);
    }

    return join(', ', $set);
  }

  protected function fixate_string($test, $alone = FALSE)
  {
    if (is_array($test)) {
      if ($alone && sizeof($test) == 1) {
        $col = key($test);
        $val = $test[$col];

        if ( ! is_numeric($col)) {
          return $this->protect_names("$val.$col");
        } else {
          return $this->fixate_string($val, TRUE);
        }
      } else {
        return array_map(array($this, 'fixate_string'), $test);
      }
    } elseif (is_string($test)) {
      return "'" . $this->real_escape($test) . "'";
    }

    return $this->ensure_type($test);
  }

}
