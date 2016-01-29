<?php

namespace Grocery\Handle;

class Hasher implements \Countable, \Serializable, \ArrayAccess, \IteratorAggregate, \JsonSerializable
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

  public function __get($key)
  {
    if (!isset($this->$key)) {
      throw new \Exception("Unknown property '$key'");
    }

    return $this->value[$key];
  }

  public function __set($key, $value)
  {
    if (!isset($this->$key)) {
      throw new \Exception("Unknown property '$key'");
    }
    $this->value[$key] = $value;
  }

  public function __isset($key)
  {
    return array_key_exists($key, $this->value);
  }

  public function __unset($key)
  {
    if (!isset($this->$key)) {
      throw new \Exception("Unknown property '$key'");
    }
    unset($this->value[$key]);
  }

  public function __toString()
  {
    return print_r($this->value, TRUE);
  }

  public function serialize()
  {
    return serialize($this->value);
  }

  public function unserialize($data)
  {
    $this->value = unserialize($data);
  }

  public function jsonSerialize() {
    return json_encode($this->value);
  }

  public function offsetSet($offset, $value)
  {
    $this->$offset = $value;
  }

  public function offsetExists($offset)
  {
    return isset($this->$offset);
  }

  public function offsetUnset($offset)
  {
    unset($this->$offset);
  }

  public function offsetGet($offset)
  {
    return $this->$offset;
  }

  public function getIterator()
  {
    return new \ArrayIterator($this->value);
  }

  public function count()
  {
    return sizeof($this->value);
  }

  public function to_json()
  {
    return $this->jsonSerialize();
  }

  public function to_s()
  {
    return (string) $this;
  }

  public function to_a()
  {
    return $this->value;
  }

}
