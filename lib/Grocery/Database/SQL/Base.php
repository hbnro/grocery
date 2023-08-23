<?php

namespace Grocery\Database\SQL;

class Base extends \Grocery\Database\Forge
{

    protected $conn = null;
    protected $setup = [];

    public function __construct($driver, $params)
    {
        $this->conn = $driver;
        $this->setup = $params;
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->conn, $method], $arguments);
    }
}
