<?php

namespace Grocery;

class Helpers
{

    private static $mask = array(
                    'numeric' => 'string',
                    'boolean' => 'integer',
                    'timestamp' => 'datetime',
                  );

    public static function hydrate($table, array $columns, array $indexes = array())
    {
        $old = $table->columns();

        foreach ($columns as $key => $val) {
            is_array($val) or $val = array($val);

            if (!\Grocery\Helpers::is_assoc($val)) {
                @list($type, $length, $default, $not_null) = $val;
                $val = compact('type', 'length', 'default', 'not_null');
            }

            if (isset($old[$key])) {
                $type = isset($val['type']) ? $val['type'] : $old[$key]['type'];
                $length = isset($val['length']) ? $val['length'] : $old[$key]['length'];
                $default = isset($val['default']) ? $val['default'] : $old[$key]['default'];
                $not_null = isset($val['not_null']) ? $val['not_null'] : $old[$key]['not_null'];

                $tmp = $old[$key]['type'];
                $left = isset(static::$mask[$tmp]) ? static::$mask[$tmp] : array_search($tmp, static::$mask);
                $right = isset(static::$mask[$type]) ? static::$mask[$type] : array_search($type, static::$mask);

                if ($left === $right) {
                    continue 1;
                }

                $tmp = compact('type', 'length', 'default', 'not_null');

                if ($tmp != $old[$key]) {
                    $table[$key] = $tmp;
                }
            } else {
                $table[$key] = $val;
            }
        }

        if (sizeof($old) <> sizeof($columns)) {
            foreach (array_keys(array_diff_key($old, $columns)) as $one) {
                unset($table[$one]);
            }
        }

        $tmp =
        $out = array();
        $idx = $table->indexes();

        foreach ($idx as $name => $set) {
            foreach ($set['column'] as $one) {
                $tmp[$one] = $set['unique'];
            }
        }

        foreach ($indexes as $key => $val) {
            $on = is_numeric($key) ? false : (bool) $val;
            $key = is_numeric($key) ? $val : $key;

            if (isset($tmp[$key])) {
                if ($on !== $tmp[$key]) {
                    $table[$key]->unindex('_');
                    $table[$key]->index('_', $on);
                }
            } else {
                $table[$key]->index('_', $on);
            }
            $out []= $key;
        }

        foreach (array_diff(array_keys($tmp), $out) as $old) {
            $table[$old]->unindex('_');
        }
    }

    public static function sql_split($test, $separator = ';')
    {
        $hash = uniqid('__SQLQUOTE__');
        $exep = preg_quote($separator, '/');

        $test = trim($test, $separator) . $separator;

        $test = str_replace("\\'", $hash, $test);
        $test = preg_replace("/{$exep}+/", $separator, $test);
        $test = preg_replace("/{$exep}\s*{$exep}/", $separator, $test);

        $query  = '';
        $length = strlen($test);

        $str = false;
        $out = array();

        for ($i = 0; $i < $length; $i += 1) {
            $char = substr($test, $i, 1);

            switch ($char) {
                case $separator;
                    if ($str !== false) {
                        $query .= $char;
                    } else {
                        if (strlen(trim($query)) == 0) {
                              continue 2;
                        }

                        $query = str_replace($exep, "\\'", $query);
                        $out []= $query;
                        $str   = false;
                        $query = '';
                    }
                break;
                case "'";
                    $str    = !$str;
                    $query .= $char;
                break;
                default;
                    $query .= $char;
                break;
            }
        }

        return $out;
    }

    public static function is_keyword($test)
    {
        return ($test == 'AND') or ($test == 'OR');
    }

    public static function is_assoc($set)
    {
        return is_array($set) && is_string(key($set));
    }

    public static function merge($as, array $are = array())
    {
        if (!empty($are[0]) && static::is_assoc($are[0])) {
            return $are[0];
        }

        $as     = preg_split('/_and_/', $as);
        $length = min(sizeof($as), sizeof($are));

        $keys   = array_slice($as, 0, $length);
        $values = array_slice($are, 0, $length);

        return $keys && $values ? array_combine($keys, $values) : array();
    }

    public static function trim($test)
    {
        return trim($test, "`\" \n\/'´");
    }

    public static function map($test, \Closure $lambda)
    {
        $out = array();

        foreach ($test as $key => $val) {
            $out[$key] = $lambda($val);
        }

        return $out;
    }
}
