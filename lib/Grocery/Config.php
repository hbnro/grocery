<?php

namespace Grocery;

class Config
{

  private static $bag = array(
                    // scheme setup
                    'unserialize' => 'ignore',
                  );



  public static function set($key, $value = NULL)
  {
    static::$bag[$key] = $value;
  }

  public static function get($key, $default = FALSE)
  {
    return isset(static::$bag[$key]) ? static::$bag[$key] : $default;
  }

}
