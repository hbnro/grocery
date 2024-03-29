<?php

namespace Grocery\Database\SQL;

class SQLException extends \Exception
{
    public $query;
}

class Query extends Dump
{

    public function select($table, $fields = '*', array $where = [], array $options = [])
    {
        return new \Grocery\Handle\Result($this->query($this->build_select($table, $fields, $where, $options)), $this, compact('table', 'where'));
    }

    public function insert($table, array $values, $column = null)
    {
        return $this->inserted($this->query($this->build_insert($table, $values, $column)));
    }

    public function delete($table, array $where = [], $limit = 0, $column = null)
    {
        return $this->affected($this->query($this->build_delete($table, $where, $limit, $column)));
    }

    public function update($table, array $fields, array $where = [], $limit = 0, $column = null)
    {
        return $this->affected($this->query($this->build_update($table, $fields, $where, $limit, $column)));
    }

    public function prepare($sql, array $vars = [])
    {
        if (\Grocery\Helpers::is_assoc($vars)) {
            $sql = strtr($sql, $this->fixate_value($vars, true));
        } else {
            $args = $this->fixate_value($vars, true);
            $sql  = preg_replace_callback('/((?<!\\\)\?)/', function () use ($args) {
                return array_shift($args);
            }, $sql);
        }

        return $sql;
    }

    public function query($sql, $repl = [])
    {
        if (func_num_args() > 1) {
            $args = func_num_args() > 2 ? func_get_args() : (array) $repl;
            $sql  = $this->prepare($sql, $args);
        }

        $out = $this->execute($this->query_repare($sql));

        if ($message = $this->has_error()) {
            $error = new SQLException("Database failure '$message'");
            $error->query = $sql;
            throw $error;
        }

        return $out;
    }

    public function result($test)
    {
        if (is_string($test)) {
            $test = $this->query($test);
        }

        return $this->fetch_result($test);
    }

    public function fetch_all($result, $context = null)
    {
        $out = [];

        if (is_string($result)) {
            $args     = func_get_args();
            $callback = strpos($result, ' ') ? 'query' : 'select';
            $result   = call_user_func_array([$this, $callback], $args);
        }

        while ($row = $this->fetch($result, $context)) {
            $out []= $row;
        }

        return $out;
    }

    public function fetch($result, $context = null)
    {
        return ($tmp = $this->fetch_assoc($result)) ? new \Grocery\Handle\Record($tmp, $this, $context) : false;
    }

    public function numrows($result)
    {
        return $this->count_rows($result);
    }

    public function affected($result)
    {
        return $this->affected_rows($result);
    }

    public function inserted($result)
    {
        return $this->last_inserted_id($result);
    }
}
