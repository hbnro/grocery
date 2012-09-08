<?php

namespace Grocery\Handle;

class Base
{

  private $value = NULL;
  private $wrapper = NULL;


  public function __construct($test, $instance)
  {
    $this->value = $test;
    $this->wrapper = $instance;
  }

  public function __call($method, $arguments)
  {
    array_unshift($arguments, $this->value);
    return call_user_func_array(array($this->wrapper, $method), $arguments);
  }

  public function __toString()
  {
    return print_r($this->value, TRUE);
  }

}
