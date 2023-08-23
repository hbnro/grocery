<?php

namespace Grocery\Database\Wrapper;

#[AllowDynamicProperties]
class MySQL
{

    private $bm = null;
    private $res = null;
    private $vval = null;

    public static function factory(array $params, $debugger)
    {
        $host  = $params['host'];
        $host .= !empty($params['port']) ? ":$params[port]" : '';

        $obj = new static;
        $obj->bm = $debugger;
        $obj->res = mysqli_connect($host, $params['user'], !empty($params['pass']) ? $params['pass'] : '');

        mysqli_select_db($obj->res, trim($params['path'], '/'));

        return $obj;
    }

    public function now()
    {
        return \Grocery\Base::plain('NOW()');
    }

    public function rand()
    {
        return \Grocery\Base::plain('RAND()');
    }

    public function stats()
    {
        return $this->bm->all();
    }

    public function version()
    {
        if ($this->vval === null) {
            $this->vval = $this->fetch_result(mysqli_query($this->res, 'SELECT version()'));
        }

        return $this->vval;
    }

    public function execute($sql)
    {
        $this->bm->start($sql);
        $out = @mysqli_query($this->res, $sql);
        $this->bm->stop();

        return $out;
    }

    public function real_escape($test)
    {
        return str_replace("'", '\\\'', stripslashes($test));
    }

    public function has_error()
    {
        return mysqli_error($this->res);
    }

    public function fetch_result($res)
    {
        return ($tmp = $this->fetch_assoc($res)) ? array_shift($tmp) : false;
    }

    public function fetch_assoc($res)
    {
        return mysqli_fetch_assoc($res);
    }

    public function count_rows($res)
    {
        return mysqli_num_rows($res);
    }

    public function affected_rows()
    {
        return mysqli_affected_rows($this->res);
    }

    public function last_inserted_id()
    {
        return mysqli_insert_id($this->res);
    }
}
