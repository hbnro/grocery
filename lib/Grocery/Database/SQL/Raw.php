<?php

namespace Grocery\Database\SQL;

class Raw
{
    private $sql;

    public function __construct($expr)
    {
        $this->sql = $expr;
    }

    public function __toString()
    {
        return $this->sql;
    }
}
