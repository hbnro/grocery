<?php

namespace Grocery;

class Base
{

  private static $multi = array();
  private static $regex = '/^\w+:|scheme\s*=\s*\w+/';

  private static $defaults = array(
                    'scheme' => 'sqlite::memory:',
                    'host' => '',
                    'port' => '',
                    'user' => '',
                    'pass' => '',
                    'path' => '',
                    'query' => '',
                    'fragment' => '',
                  );

  public static $available = array(
                    // back to basics
                    'pgsql' => '\\Grocery\\Database\\@\\PgSQL',
                    'mysql' => '\\Grocery\\Database\\@\\MySQL',
                    'sqlite' => '\\Grocery\\Database\\@\\SQLite',
                  );

  public static function connect($to)
  {
    if (!preg_match(static::$regex, $to)) {
      throw new \Exception("Unable to determine connection scheme for '$to'");
    } elseif (!isset(static::$multi[$to])) {
      if (strrpos($to, ';')) {
        $params = array();

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

  public static function factory(array $params = array(), $raw = FALSE)
  {
    $params = array_merge(static::$defaults, $params);

    if (!array_key_exists($params['scheme'], static::$available)) {
      throw new \Exception("Scheme not available for '$params[scheme]'");
    }

    $pdo = ($params['fragment'] == 'pdo') OR array_key_exists('pdo', $params);

    $base_klass = static::$available[$params['scheme']];
    $driver_klass = $pdo ? '\\Grocery\\Database\\Wrapper\\PDO' : str_replace('@', 'Wrapper', $base_klass);
    $scheme_klass = str_replace('@', 'Scheme', $base_klass);

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

}
