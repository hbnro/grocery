<?php

namespace Grocery;

class Config
{

    private static $bag = [
                    // debug
                    'logger' => '',
                    // scheme setup
                    'unserialize' => 'ignore',
                  ];

    public static function set($key, $value = null)
    {
        static::$bag[$key] = $value;
    }

    public static function get($key, $default = false)
    {
        return isset(static::$bag[$key]) ? static::$bag[$key] : $default;
    }
}
