<?php

namespace Grocery;

class Helpers
{

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

    $str = FALSE;
    $out = array();

    for ($i = 0; $i < $length; $i += 1) {
      $char = substr($test, $i, 1);

      switch ($char) {
        case $separator;
          if ($str !== FALSE) {
            $query .= $char;
          } else {
            if (strlen(trim($query)) == 0) {
              continue;
            }

            $query = str_replace($exep, "\\'", $query);
            $out []= $query;
            $str   = FALSE;
            $query = '';
          }
        break;
        case "'";
          $str    = ! $str;
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
    return preg_match('/^(?:and|not|x?or)$/i', $test) > 0;
  }

  public static function is_assoc($set)
  {
    return is_array($set) && is_string(key($set));
  }

  public static function merge($as, array $are = array())
  {
    if ( ! empty($are[0]) && static::is_assoc($are[0])) {
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
