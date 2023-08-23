<?php

namespace Grocery\Database\SQL;

class Raw
{
    private $sql;
    private $expr;

    public function __construct($sql)
    {
        $this->expr = $sql;
    }

    public function __toString()
    {
        return $this->expr;
    }
}
