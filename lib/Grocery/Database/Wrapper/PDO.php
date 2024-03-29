<?php

namespace Grocery\Database\Wrapper;

#[AllowDynamicProperties]
class PDO
{

    private $bm = null;
    private $res = null;

    public static function factory(array $params, $debugger)
    {
        switch ($params['scheme']) {
            case 'sqlite';
                $dsn_string = 'sqlite:' . strtr($params['host'] . $params['path'], '\\', '/');
          break;
            default;
                $dsn_string = "$params[scheme]:host=$params[host]";

                if ($params['port'] > 0) {
                    $dsn_string .= ";port=$params[port]";
                }

                $dsn_string .= ';dbname=' . trim($params['path'], '/');
          break;
        }

        parse_str($params['query'], $query);

        $obj = new static;
        $obj->bm = $debugger;
        $obj->res = new \PDO($dsn_string, $params['user'], $params['pass'], $query);

        return $obj;
    }

    public function now()
    {
        if ($this->res->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            return \Grocery\Base::plain('CURRENT_TIMESTAMP');
        }
        return \Grocery\Base::plain('NOW()');
    }

    public function rand()
    {
        if ($this->res->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            return \Grocery\Base::plain('RAND()');
        }
        return \Grocery\Base::plain('RANDOM()');
    }

    public function stats()
    {
        return $this->bm->all();
    }

    public function version()
    {
        $test = $this->res->getAttribute(\PDO::ATTR_SERVER_VERSION);

        return isset($test['versionString']) ? $test['versionString'] : $test;
    }

    public function execute($sql)
    {
        $this->bm->start($sql);
        if (strtolower(substr(trim($sql), 0, 7)) === 'update') {
            $out = @$this->res->exec($sql);
        }
        $out = @$this->res->query($sql);
        $this->bm->stop();

        return $out;
    }

    public function real_escape($test)
    {
        return substr($this->res->quote($test), 1, -1);
    }

    public function has_error()
    {
        $test = $this->res->errorInfo();

        return $test[0] == '00000' ? false : $test[2];
    }

    public function fetch_result($res)
    {
        return ($test = $this->fetch_assoc($res)) ? array_shift($test) : false;
    }

    public function fetch_assoc($res)
    {
        return $res ? $res->fetch(\PDO::FETCH_ASSOC) : false;
    }

    public function fetch_object($res)
    {
        return $res ? $res->fetch(\PDO::FETCH_OBJ) : false;
    }

    public function count_rows($res)
    {
        if (!$res) {
            return false;
        }

        $out = $res->rowCount();

        if (preg_match('/^\s*SELECT.+?FROM(.+?)$/is', $res->queryString, $match)) {
          // http://www.php.net/manual/es/pdostatement.rowcount.php
            $tmp = $this->execute("SELECT COUNT(*) FROM $match[1]");
            $out = $this->fetch_result($tmp);
        }

        return (int) $out;
    }

    public function affected_rows($res)
    {
        return $res ? $res->rowCount() : false;
    }

    public function last_inserted_id($res)
    {
        return $this->fetch_result($res);
    }
}
