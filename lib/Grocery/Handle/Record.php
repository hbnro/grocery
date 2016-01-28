<?php

namespace Grocery\Handle;

class Record extends Hasher
{

  private $data = array();

  public function __construct($row)
  {
    $this->data = $row;
  }

  public function __get($key)
  {
    if (!isset($this->$key)) {
      throw new \Exception("Unknown property '$key'");
    }

    return $this->data[$key];
  }

  public function __set($key, $value)
  {
    if (!isset($this->$key)) {
      throw new \Exception("Unknown property '$key'");
    }
    $this->data[$key] = $value;
  }

  public function __isset($key)
  {
    return array_key_exists($key, $this->data);
  }

  public function __unset($key)
  {
    if (!isset($this->$key)) {
      throw new \Exception("Unknown property '$key'");
    }
    unset($this->data[$key]);
  }

  public function __call($method, $arguments)
  {
    throw new \Exception("Cannot execute '$method()'");
  }

  public function __toString()
  {
    return $this->to_s();
  }

  public function serialize()
  {
    return serialize($this->data);
  }

  public function unserialize($data)
  {
    $this->data = unserialize($data);
  }

  public function jsonSerialize() {
    return $this->data;
  }

  public function getIterator()
  {
    return new \ArrayIterator($this->data);
  }

  public function count()
  {
    return sizeof($this->data);
  }

  public function to_json()
  {
    return json_encode($this->data);
  }

  public function to_s()
  {
    return print_r($this->data, TRUE);
  }

  public function to_a()
  {
    return $this->data;
  }

}
