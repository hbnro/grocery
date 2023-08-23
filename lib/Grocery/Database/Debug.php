<?php

namespace Grocery\Database;

class Debug
{

    private $logger = null;

    private $queries = [
            'sql' => [],
            'ms' => [],
          ];

    public function __construct($callback)
    {
        $this->logger = $callback;
    }

    public function last($key = false)
    {
        return !empty($this->queries[$key]) ? end($this->queries[$key]) : false;
    }

    public function start($sql)
    {
        $this->queries['sql'] []= $sql;
        $this->queries['ms'] []= microtime(true);
    }

    public function stop()
    {
        if (is_callable($this->logger)) {
            call_user_func($this->logger, $this->last('sql'), microtime(true) - $this->last('ms'));
        }
    }

    public function all()
    {
        return $this->queries;
    }
}
