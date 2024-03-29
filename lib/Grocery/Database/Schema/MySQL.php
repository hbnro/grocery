<?php

namespace Grocery\Database\Schema;

class MySQL extends \Grocery\Database\SQL\Schema
{

    public static $types = [
                  'VARCHAR' => 'string',
                  'LONGTEXT' => 'string',
                  'TINYTEXT' => 'string',
                  'INT' => 'integer',
                  'TINYINT' => 'integer',
                  'SMALLINT' => 'integer',
                  'MEDIUM' => 'integer',
                  'BIGINT' => 'integer',
                  'NUMERIC' => 'numeric',
                  'DECIMAL' => 'numeric',
                  'YEAR' => 'numeric',
                  'DOUBLE' => 'float',
                  'BOOL' => 'boolean',
                  'BINARY' => 'binary',
                  'VARBINARY' => 'binary',
                  'LONGBLOB' => 'binary',
                  'MEDIUMBLOB' => 'binary',
                  'TINYBLOB' => 'binary',
                  'BLOB' => 'binary',
                  'DATETIME' => 'timestamp',
                ];

    public static $raw = [
                  'primary_key' => 'INT(11) auto_increment PRIMARY KEY',
                  'string' => ['type' => 'VARCHAR', 'length' => 255],
                  'integer' => ['type' => 'INT', 'length' => 11],
                  'timestamp' => 'DATETIME',
                  'numeric' => ['type' => 'VARCHAR', 'length' => 16],
                  'boolean' => ['type' => 'TINYINT', 'length' => 1],
                  'binary' => 'BLOB',
                ];

    private static $rename_col = [
                    '/^VARCHAR$/' => 'VARCHAR(255)',
                    '/^INT(?:EGER)$/' => 'INT(11)',
                  ];

    public function rename($from, $to)
    {
        return $this->execute(sprintf('RENAME TABLE `%s` TO `%s`', $from, $to));
    }

    public function add_column($to, $name, $type)
    {
        return $this->execute(sprintf('ALTER TABLE `%s` ADD `%s` %s', $to, $name, $this->build_field($type)));
    }

    public function remove_column($from, $name)
    {
        return $this->execute(sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', $from, $name));
    }

    public function rename_column($from, $name, $to)
    {
        $set  = $this->columns($from);
        $type = $this->build_field($set[$name]['type'], $set[$name]['length']);
        $type = preg_replace(array_keys(static::$rename_col), static::$rename_col, $type);

        return $this->execute(sprintf('ALTER TABLE `%s` CHANGE `%s` `%s` %s', $from, $name, $to, $type));
    }

    public function change_column($from, $name, $to)
    {
        return $this->execute(sprintf('ALTER TABLE `%s` MODIFY `%s` %s', $from, $name, $this->build_field($to)));
    }

    public function add_index($to, $name, $column, $unique = false)
    {
        return $this->execute($this->build_index($to, $name, compact('column', 'unique')));
    }

    public function build_index($to, $name, array $params)
    {
        return sprintf('CREATE%sINDEX `%s` ON `%s` (`%s`)', $params['unique'] ? ' UNIQUE ' : ' ', $name, $to, join('`, `', $params['column']));
    }

    public function remove_index($from, $name)
    {
        return $this->execute(sprintf('DROP INDEX `%s` ON `%s`', $name, $from));
    }

    public function begin_transaction()
    {
        return $this->execute('BEGIN TRANSACTION');
    }

    public function commit_transaction()
    {
        return $this->execute('COMMIT TRANSACTION');
    }

    public function rollback_transaction()
    {
        return $this->execute('ROLLBACK TRANSACTION');
    }

    public function set_encoding()
    {
        return $this->execute('SET NAMES UTF8');
    }

    public function fetch_tables()
    {
        $out = [];
        $old = $this->execute('SHOW TABLES');

        while ($row = $this->fetch_assoc($old)) {
            $out []= array_pop($row);
        }

        return $out;
    }

    public function fetch_columns($test)
    {
        $out = [];
        $old = $this->execute("DESCRIBE `$test`");

        while ($row = $this->fetch_assoc($old)) {
            preg_match('/^(\w+)(?:\((\d+)\))?.*?$/', strtoupper($row['Type']), $match);

            $out[$row['Field']] = [
                'type' => $row['Extra'] == 'auto_increment' ? 'PRIMARY_KEY' : $match[1],
                'length' => !empty($match[2]) ? (int) $match[2] : 0,
                'default' => \Grocery\Base::plain($row['Default']),
                'not_null' => $row['Null'] <> 'YES',
            ];
        }

        return $out;
    }

    public function fetch_indexes($test)
    {
        $out = [];

        $res = $this->execute("SHOW INDEXES FROM `$test`");

        while ($one = $this->fetch_assoc($res)) {
            if ($one['Key_name'] <> 'PRIMARY') {
                if (!isset($out[$one['Key_name']])) {
                    $out[$one['Key_name']] = [
                        'unique' => !$one['Non_unique'],
                        'column' => [],
                    ];
                }

                $out[$one['Key_name']]['column'] []= $one['Column_name'];
            }
        }

        return $out;
    }

    public function quote_string($test)
    {
        return "`$test`";
    }

    public function ensure_limit($test)
    {
        return "\nLIMIT $test[1]" . (!empty($test[2]) ? ",$test[2]\n" : "\n");
    }

    public function ensure_type($test)
    {
        if (is_bool($test)) {
            $test = $test ? 1 : 0;
        } elseif ($test === null) {
            $test = 'NULL';
        }

        return $test;
    }
}
