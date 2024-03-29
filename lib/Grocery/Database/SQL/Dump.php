<?php

namespace Grocery\Database\SQL;

class Dump extends Base
{

    protected static $regex = [
                    'delete' => '/^\s*DELETE\s+FROM\s+(\S+)\s*$/is',
                    'limit' => '/\s+LIMIT\s+(\d+)(?:\s*(?:,|\s+TO\s+)\s*(\d+))?\s*$/i',
                  ];

    protected function build_field($type, $length = 0, $default = null, $not_null = false)
    {
        $tmp = static::$raw;

        if (empty($type)) {
            return false;
        } else {
            $test = is_string($type) && !empty($tmp[$type]) ? $tmp[$type] : $type;
        }

        if (\Grocery\Helpers::is_assoc($test)) {
            $test = array_merge(compact('length', 'default', 'not_null'), $test);

            $type     = isset($test['type']) ? $test['type'] : $type;
            $length   = isset($test['length']) ? $test['length'] : $length;
            $default  = isset($test['default']) ? $test['default'] : $default;
            $not_null = isset($test['not_null']) ? $test['not_null'] : $not_null;
        } elseif (is_array($test)) {
            @list($type, $length, $default, $not_null) = $test;
        } elseif ($test !== $type) {
            return $test;
        }

        if (!empty($tmp[$type])) {
            if (is_string($tmp[$type])) {
                return $tmp[$type];
            }

            $length or $length = isset($tmp[$type]['length']) ? $tmp[$type]['length'] : 0;
            $type = $tmp[$type]['type'];
        }

        $type  = strtoupper($type);
        $type .= $length > 0 ? sprintf('(%d)', $length) : '';
        $type .= $not_null ? ' NOT NULL' : '';

        if ($default) {
            $type .= ' DEFAULT ' . $this->fixate_value($default);
        }

        return $type;
    }

    protected function build_table($name, array $columns = [])
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

    protected function build_select($table, $fields = '*', array $where = [], array $options = [])
    {
        if (!empty($options['join'])) {
            $sql = $this->build_joins($table, $fields, $options['join']);
        } else {
            $sql  = "SELECT\n" . $this->build_fields($fields);
            $sql .= "\nFROM\n" . $this->build_fields($table);
        }

        if (!empty($where)) {
            $sql .= "\nWHERE\n" . $this->build_where($where, 'AND', $table);
        }

        if (!empty($options['group_by'])) {
            $sql .= "\nGROUP BY";

            if (is_array($options['group_by'])) {
                $sub = [];

                foreach ($options['group_by'] as $one) {
                    $sub []= $this->protect_names("$table.$one");
                }
                $sql .= "\n " . join(",\n ", $sub);
            } else {
                $sql .= "\n " . $this->protect_names("$table.$options[group]");
            }
        }

        if (!empty($options['order_by'])) {
            $inc  = 0;
            $sql .= "\nORDER BY";

            foreach ((array) $options['order_by'] as $one => $set) {
                if (($inc += 1) > 1) {
                    $sql .= ', ';
                }

                if (is_numeric($one)) {
                    $sql .= "\n ";
                    if ($set instanceof Raw) {
                        $sql .= $set->__toString();
                        continue 1;
                    } else {
                        $order = strtoupper($set[1]);
                        $sql .= $this->protect_names("$table.$set[0]") . " $order";
                    }
                    continue 1;
                }

                $one  = $this->protect_names("$table.$one");
                $sql .= "\n $one " . (is_numeric($set) ? ($set > 0 ? 'ASC' : 'DESC') : strtoupper($set));
            }
        }

        $limit  = !empty($options['limit']) ? $options['limit'] : 0;
        $offset = !empty($options['offset']) ? $options['offset'] : 0;

        if ($limit > 0) {
            $sql .= "\nLIMIT " . ($offset > 0 ? "$offset, " : '') . $limit;
        }

        return $sql;
    }

    protected function build_insert($table, array $values, $primary_key = null)
    {
        $sql  = "INSERT INTO\n" . $this->build_fields($table);
        $sql .= $this->build_values($values, true);

        return $this->query_repare($sql, $primary_key);
    }

    protected function build_delete($table, array $where = [], $limit = 0, $primary_key = null)
    {
        $sql = "DELETE FROM\n" . $this->build_fields($table);
        $sql .= $where ? "\nWHERE\n" . $this->build_where($where) : '';
        $sql .= $limit > 0 ? "\nLIMIT $limit" : '';

        return $this->query_repare($sql, $primary_key);
    }

    protected function build_update($table, array $fields, array $where = [], $limit = 0, $primary_key = null)
    {
        $sql  = "UPDATE\n" . $this->build_fields($table);
        $sql .= "\nSET\n" . $this->build_values($fields, false);
        $sql .= $where ? "\nWHERE\n" . $this->build_where($where) : '';
        $sql .= $limit > 0 ? "\nLIMIT {$limit}" : '';

        return $this->query_repare($sql, $primary_key);
    }

    protected function build_joins($table, array $fields, array $set)
    {
        $sub =
        $out = [];

        if (!isset($set[0])) {
            $set = [$set];
        }

        foreach ($fields as $k => $v) {
            is_numeric($k) && $sub []= "$table.$v";
            is_numeric($k) or $sub["$table.$k"] = $v;
        }

        foreach ($set as $one) {
          // TODO: throw exception if no-defaults?
            $name = !empty($one['table']) ? $one['table'] : 'unknown';

            isset($one['select']) or $one['select'] = '*';

            foreach ((array) $one['select'] as $key => $val) {
                is_numeric($key) && $sub []= "$name.$val";
                is_numeric($key) or $sub["$name.$key"] = $val;
            }

            $use = !empty($one['use']) ? strtoupper($one['use']) : 'LEFT';
            $fk = $this->protect_names(!empty($one['field']) ? $one['field'] : "{$name}_id");

            $out []= "$use JOIN " . $this->protect_names($name) . ' ON';
            $out []= ' ' . $this->protect_names("$name.id") . ' = ' . $this->protect_names("$table.$fk");
        }

        array_unshift($out, "FROM\n" . $this->build_fields($table));
        array_unshift($out, "SELECT\n" . $this->build_fields($sub));

        return join("\n", $out);
    }

    protected function build_fields($values)
    {
        $sql = [];

        foreach ((array) $values as $key => $val) {
            if (strlen(trim($val)) == 0) {
                continue 1;
            } elseif (is_numeric($key)) {
                $sql []= ' ' . $this->protect_names($val);
                continue 1;
            }

            $sql []= ' ' . $this->protect_names($key) . ' AS ' . $this->quote_string($val);
        }

        return join(",\n", $sql);
    }

    protected function build_values($fields, $insert = false)
    {
        $sql    = [];
        $fields = (array) $fields;

        if ($insert) {
            $cols = [];

            foreach (array_keys($fields) as $one) {
                $cols []= $this->quote_string($one);
            }

            $sql []= '(' . join(', ', $cols) . ')';
            $sql []= "\nVALUES\n(\n";
        }

        $out   = [];
        $count = 0;
        $total = sizeof($fields);

        foreach ($fields as $key => $val) {
            if (is_numeric($key)) {
                $out []= $val;
            } else {
                $val = $this->fixate_value($val);
                $val = is_numeric($val) ? $val : $val;

                if ($insert) {
                    $out []= $val;
                } else {
                    $out []= $this->quote_string($key) . " = $val";
                }
            }
        }

        $sql []= ' ' . join(",\n ", $out);

        if ($insert) {
            $sql []= "\n)";
        }

        return join('', $sql);
    }

    protected function build_where($test, $operator = 'AND', $supertable = false)
    {
        $sql        = [];
        $operator   = strtoupper($operator);
        $sub_prefix = $supertable ? $this->protect_names($supertable) . '.' : '';

        foreach ($test as $key => $val) {
            if (is_numeric($key)) {
                if (!\Grocery\Helpers::is_assoc($val)) {
                    $raw = array_shift($val);
                    if ($val && strpos($raw, '?')) {
                        $sql []= $this->prepare($raw, $val);
                    } else {
                        array_unshift($val, $raw) && $sql []= join("\n", $val);
                    }
                } else {
                    $sql []= is_array($val) ? $this->build_where($val, $operator, $supertable) : $val;
                }
            } elseif (\Grocery\Helpers::is_keyword($key)) {
                $sql []= '(' . trim($this->build_where($val, strtoupper($key), $supertable)) . ')';
            } elseif (preg_match('/_(?:and|or)_/i', $key, $match)) {
                $sub = [];
                foreach (explode($match[0], $key) as $one) {
                    $sub[$one] = $val;
                }
                $sql []= '(' . trim($this->build_where($sub, strtoupper(trim($match[0], '_')), $supertable)) . ')';
            } elseif (preg_match('/^(.+?)(?:\s+(!=?|[<>]=?|<>|NOT|R?LIKE)\s*)?$/', $key, $match)) {
                $sub = '';
                $key = $this->protect_names($match[1]);

                if ($val === null) {
                    $sub = 'IS NULL';
                } else {
                    $val = $this->fixate_value($val, true);
                    $sub = !empty($match[2]) ? ($match[2] == '!' ? '!=' : $match[2]) : '=';
                }

                if (is_array($val) && (sizeof($val) > 1)) {
                    if (array_filter($val, 'is_array')) {
                        $key = $match[1];
                        $debug = json_encode($val);

                        throw new \Exception("Invalid '{$key}' field, given '{$debug}'");
                    }

                    $key  .= in_array($sub, ['!=', '<>']) ? ' NOT' : '';
                    $sql []= " $sub_prefix$key IN(" . join(', ', $val) . ")";
                } else {
                    $val   = is_array($val) ? array_shift($val) : $val;
                    $sql []= " $sub_prefix$key $sub $val";
                }
            }
        }

        return join("\n$operator\n", $sql);
    }

    protected function query_repare($test, $pk = null)
    {
        if (method_exists($this, 'ensure_id') && $pk) {
            $test = $this->ensure_id($test, $pk);
        } else {
            if (method_exists($this, 'ensure_limit')) {
                $test = preg_replace_callback(self::$regex['limit'], [$this, 'ensure_limit'], $test);
            }

            $test = preg_replace(self::$regex['delete'], 'DELETE FROM \\1 WHERE 1=1', $test);
        }

        return $test;
    }

    protected function protect_names($test)
    {
        if ($test instanceof Raw) {
            return $test->__toString();
        }

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

        return join(",\n ", $set);
    }

    protected function fixate_value($test, $map = false)
    {
        if (is_array($test) && $map) {
            return array_map([$this, 'fixate_value'], $test);
        } elseif (is_string($test)) {
            return "'" . $this->real_escape($test) . "'";
        } elseif ($test instanceof Raw) {
            return $test->__toString();
        }

        return $this->ensure_type($test);
    }
}
