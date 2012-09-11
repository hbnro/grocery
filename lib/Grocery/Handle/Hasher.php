<?php

namespace Grocery\Handle;

class Hasher extends Base implements \Countable, \Serializable, \ArrayAccess, \IteratorAggregate
{

  public function serialize()
  {
    return serialize(array());
  }

  public function unserialize($data)
  {
    var_dump(unserialize($data));
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
    return new \ArrayIterator(array());
  }

  public function count()
  {
    return -1;
  }

}
