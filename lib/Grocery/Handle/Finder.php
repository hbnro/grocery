<?php

namespace Grocery\Handle;

class Finder extends Hasher
{

    private $offset = null;

    private $params = array();

    private static $chained = array(
                    'where',
                    'limit',
                    'offset',
                    'group_by',
                    'order_by',
                    'join',
                    'get',
                  );

    private static $retrieve = array(
                    'get',
                    'all',
                    'pick',
                    'first',
                    'each',
                    'count',
                  );

    private static $options = array(
                    'select' => array(),
                    'where' => array(),
                  );

    public function __get($key)
    {
        if (!isset($this->$key)) {
            throw new \Exception("Field '$this'.'$key' does not exists");
        }

        $this->offset = $key;

        return $this;
    }

    public function __set($key, $value)
    {
        $exists = isset($this->$key);

        if ($value === null) {
            unset($this[$key]);
        } elseif (is_string($value)) {
            if (!$exists) {
                throw new \Exception("Field '$this'.'$key' does not exists");
            } elseif (strlen(trim($value)) === 0) {
                throw new \Exception("Cannot rename the field '$this'.'$key' to $value'");
            } elseif (isset($this->$value)) {
                throw new \Exception("Cannot rename the field '$this'.'$key' to '$value' (already exists)");
            }
            $this->rename_column($key, $value);
        } elseif (is_array($value)) {
            if (sizeof($value = (array) $value) === 0) {
                throw new \Exception("Missing field definition for '$this'.'$key'");
            }
            isset($this->$key) ? $this->change_column($key, $value) : $this->add_column($key, $value);
        } elseif ($value instanceof \Closure) {
            echo "FILTER TO: $this.$key ";
        } else {
            throw new \Exception("Nothing to do with '$value' on '$this'.'$key'");
        }
    }

    public function __isset($key)
    {
        return array_key_exists($key, $this->columns());
    }

    public function __unset($key)
    {
        if (!isset($this[$key])) {
            throw new \Exception("Field '$this'.'$key' does not exists");
        }
        $this->remove_column($key);
    }

    public function __call($method, $arguments)
    {
        switch ($method) {
            case in_array($method, static::$retrieve);
                $params = array_merge(static::$options, $this->params);
                $this->params = array();

                switch ($method) {
                    case 'all';
                    case 'get';
                    case 'pick';
                    case 'first';
                        $limit = array_shift($arguments) ?: ($method === 'all' ? 0 : 1);

                        if ($limit > 1) {
                            $params['limit'] = $limit;
                        }

                        $result = $this->select($params['select'] ?: '*', $params['where'], $params);

                    return $limit <> 1 ? $result->fetch_all() : $result->fetch();
                    case 'each';
                        @list($lambda) = $arguments;

                        if ($lambda instanceof \Closure) {
                              $result = $this->select($params['select'] ?: '*', $params['where'], $params);

                            while ($row = $result->fetch()) {
                                $lambda($row);
                            }

                              return;
                        }
                    case 'count';

                    return (int) $this->select('COUNT(*)', $params['where'], $params)->result();
                    default;
                    throw new \Exception("Invalid parameters on '$method()'");
                }
            case in_array($method, static::$chained);
                if (sizeof($arguments) === 0) {
                    throw new \Exception("Missing arguments for '$method()'");
                    ;
                } elseif (isset($this->params[$method])) {
                    array_unshift($arguments, $this->params[$method]);
                }

                @list($first) = $arguments;
                $method = str_replace('get', 'select', $method);

                $this->params[$method] = $first;

            return $this;
            case 'index';
                @list($name, $unique) = $arguments;

            return $this->add_index("{$this}_{$this->offset}_{$name}_idx", array($this->offset), !!$unique);
            case 'unindex';
                @list($name) = $arguments;

            return $this->remove_index("{$this}_{$this->offset}_{$name}_idx", (string) $this);
            default;

            return parent::__call($method, $arguments);
        }
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->all());
    }

    public function count(): int
    {
        return $this->__call('count', array());
    }

    public function get($key, $fallback = null)
    {
        return isset($this->params[$key]) ? $this->params[$key] : $fallback;
    }
}
