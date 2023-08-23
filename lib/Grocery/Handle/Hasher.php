<?php

namespace Grocery\Handle;

class Hasher implements \Countable, \ArrayAccess, \IteratorAggregate, \JsonSerializable
{

    private $value = null;
    private $wrapper = null;
    private $settings = null;

    public function __construct($test, $result, $context = [])
    {
        $this->value = $test;
        $this->wrapper = $result;
        $this->settings = $context;
    }

    public function __call($method, $arguments)
    {
        array_unshift($arguments, $this->value);

        if (in_array($method, ['fetch', 'fetch_all'])) {
            $arguments []= $this->settings;
        }

        if ($method === 'update') {
            $arguments[0] = $this->get('table') ?: $arguments[0];
            $arguments[2] = !empty($arguments[2]) ? $arguments[2] : $this->get('where');
        }

        if ($method === 'delete') {
            $arguments[0] = $this->get('table') ?: $arguments[0];
            if (isset($arguments[1]) && is_numeric($arguments[1])) {
                $arguments[2] = $arguments[1];
            }
            $arguments[1] = isset($arguments[1]) && is_array($arguments[1]) ? $arguments[1] : $this->get('where');
        }

        return call_user_func_array([$this->wrapper, $method], $arguments);
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
        return print_r($this->value, true);
    }

    public function __serialize()
    {
        return serialize($this->value);
    }

    public function __unserialize($data)
    {
        $this->value = unserialize($data);
    }

    public function jsonSerialize(): mixed
    {
        return json_encode($this->value);
    }

    public function offsetSet($offset, $value): void
    {
        $this->$offset = $value;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetUnset($offset): void
    {
        unset($this->$offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->$offset;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->value);
    }

    public function count(): int
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

    public function get($key, $or = null)
    {
        return isset($this->settings[$key]) ? $this->settings[$key] : $this->params->get($key, $or);
    }
}
