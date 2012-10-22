<?php

namespace Grocery\Handle;

class Table extends Hasher
{

  public function __get($key)
  {
    return new \Grocery\Handle\Finder($key, $this);
  }

  public function __set($key, $value)
  {
    $exists = isset($this->$key);

    if ($value === NULL) {
      unset($this[$key]);
    } elseif (is_string($value)) {
      if ( ! $exists) {
        throw new \Exception("Table '$key' does not exists");
      } elseif (strlen(trim($value)) === 0) {
        throw new \Exception("Cannot rename the table '$key' to '$value'");
      } elseif (isset($this->$value)) {
        throw new \Exception("Cannot rename the table '$key' to '$value' (already exists)");
      }
      $this->rename($key, $value);
    } elseif (is_array($value)) {
      if ($exists) {
        throw new \Exception("Table '$key' already exists");
      } elseif (sizeof($value) === 0) {
        throw new \Exception("Empty fields for '$key'");
      } elseif ( ! \Grocery\Helpers::is_assoc($value)) {
        throw new \Exception("Invalid fields for '$key'");
      }
      $this->create($key, $value);
    } else {
      throw new \Exception("Nothing to do with '$value' on '$key'");
    }
  }

  public function __isset($key)
  {
    return in_array($key, $this->tables());
  }

  public function __unset($key)
  {
    if ( ! isset($this->$key)) {
      throw new \Exception("Table '$key' does not exists");
    }
    $this->drop($key);
  }

  public function __toString()
  {
    if ( ! method_exists($this, 'build_table')) {
      return $this->build_table($this->columns());
    }
    return $this->to_s();
  }


  public function serialize()
  {
    $params = $this->setup;
    $tables = $this->scheme();

    return serialize(compact('params', 'tables'));
  }

  public function unserialize($data)
  {
    extract(unserialize($data));

    $this->setup = $params;
    $this->conn = \Grocery\Base::factory($params, TRUE);
    $type = \Grocery\Config::get('unserialize');

    if ($type === 'reset') {
      $this->reset();
    }


    foreach ($tables as $one => $set) {
      if ($type === 'overwrite') {
        $this->drop($one);
      }

      if ( ! isset($this->$one)) {
        $this->create($one, $set['columns']);

        foreach ($set['indexes'] as $key => $val) {
          $this->add_index($one, $key, $val['column'], $val['unique']);
        }
      }
    }
  }

  public function getIterator()
  {
    $test = array();
    foreach ($this->tables() as $one) {
      $test[$one] = new \Grocery\Handle\Table($one, $this);
    }
    return new \ArrayIterator($test);
  }

  public function count()
  {
    return sizeof($this->tables());
  }


  public function to_json()
  {
    return json_encode($this->to_a());
  }

  public function to_s()
  {
    $out = array();
    foreach ($this->tables() as $one) {
      $out []= $this->build_table($one, $this->columns($one));

      foreach ($this->indexes($one) as $name => $set) {
        $out []= $this->build_index($one, $name, $set);
      }
    }
    return join(";\n", $out);
  }

  public function to_a()
  {
    return $this->scheme();
  }

}
