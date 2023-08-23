<?php

namespace Grocery;

class Base
{

    private static $multi = [];
    private static $regex = '/^\w+:|scheme\s*=\s*\w+/';

    private static $defaults = [
                    'scheme' => 'sqlite::memory:',
                    'host' => '',
                    'port' => '',
                    'user' => '',
                    'pass' => '',
                    'path' => '',
                    'query' => '',
                    'fragment' => '',
                  ];

    public static $available = [
                    // back to basics
                    'pgsql' => '\\Grocery\\Database\\@\\PgSQL',
                    'mysql' => '\\Grocery\\Database\\@\\MySQL',
                    'sqlite' => '\\Grocery\\Database\\@\\SQLite',
                  ];

    public static $types = [
                    'pk' => 'primary_key',
                    'str' => 'string',
                    'int' => 'integer',
                    'time' => 'timestamp',
                    'date' => 'datetime',
                    'num' => 'numeric',
                    'bool' => 'boolean',
                    'blob' => 'binary',
                  ];

    public static function __callStatic($method, $arguments)
    {
        if (!isset(static::$types[$method])) {
            throw new \Exception("Unknown database method '$methd'");
        }

        return array_merge(['type' => static::$types[$method]], !empty($arguments[0]) ? $arguments[0] : []);
    }

    public static function connect($to)
    {
        if (!preg_match(static::$regex, $to)) {
            throw new \Exception("Unable to determine connection scheme for '$to'");
        } elseif (!isset(static::$multi[$to])) {
            if (strrpos($to, ';')) {
                $params = [];

                $old = explode(';', $to);
                $old = array_map('trim', $old);

                foreach ($old as $one) {
                    $new = explode('=', $one, 2);
                    $key = trim(array_shift($new));

                    $params[$key] = trim(join('', $new));
                }
            } else {
                $params = (array) @parse_url($to);
            }

            static::$multi[$to] = static::factory($params);
        }

        return static::$multi[$to];
    }

    public static function factory(array $params = [], $raw = false)
    {
        $params = array_merge(static::$defaults, $params);

        if (!array_key_exists($params['scheme'], static::$available)) {
            throw new \Exception("Scheme not available for '$params[scheme]'");
        }

        $pdo = ($params['fragment'] == 'pdo') or array_key_exists('pdo', $params);

        $base_klass = static::$available[$params['scheme']];
        $driver_klass = $pdo ? '\\Grocery\\Database\\Wrapper\\PDO' : str_replace('@', 'Wrapper', $base_klass);
        $scheme_klass = str_replace('@', 'Schema', $base_klass);

        $debugger = new \Grocery\Database\Debug(\Grocery\Config::get('logger'));
        $wrapper = $driver_klass::factory($params, $debugger);

        if ($raw) {
            return $wrapper;
        }

        $obj = new $scheme_klass($wrapper, $params);

      // TODO: enable configuration for this?
        method_exists($obj, 'set_encoding') && $obj->set_encoding();

        return $obj;
    }

    public static function plain($sql)
    {
        return new \Grocery\Database\SQL\Raw($sql);
    }
}
