The main purpose
================

Provide a simple (and fancy) interface to work with databases, currently supports
PostgreSQL (8.4+), MySQLi (5.1+) and SQLite3 (3.7+) using the same API.

Perform migrations quickly, although you must write a wrapper for this
if you want to keep the track.

[![CI](https://github.com/hbnro/grocery/actions/workflows/ci.yml/badge.svg)](https://github.com/hbnro/grocery/actions)

## First steps

You can just load tables and work with them almost easily, or you could create
tables directly from the code and skip the CLI or GUI to achieve that.

```php
<?php

# configure the DSN
$dsn = 'pgsql://postgres:test@localhost:5432/test#pdo';

# create a connection
$db = Grocery\Base::connect($dsn);

# load existing table
$foo = $db['my_table'];

# pick one row randomly
$bar = $foo->select('*', [/* where */], [
  'order' => array('random'),
]);

# create another table
$db['other_table'] = [
  'id' => 'primary_key',
  'title' => 'string',
  'published_at' => 'timestamp',
];

# inserting a new row
$db->other_table->insert([
  'title' => 'Hello World!',
  'published_at' => date('Y-m-d H:i:s'),
]);
```

There are generic types for creating new tables.

 - **primary_key**: The most common field, conventionally named _id_
 - **integer**: Basic numeric types, for relations, counting, etc.
 - **float**: Extended numeric types for floating point values
 - **numeric**: For money, decimals and other non-float values
 - **string**: Varchar, char, etc. Default to 255 max-length
 - **text**: For plain text without limits, depending on its driver
 - **binary**: Blob values, but please do not save your files on the database
 - **boolean**: Real booleans, chars or tiny-ints depending on its driver
 - **timestamp**: Not *nix timestamp, alias for datetime indeed
 - **datetime**: Common date+time strings like `date('Y-m-d H:i:s')`
 - **date**: Just the date part `Y-m-d`
 - **time**: The time part `H:i:s`

## About migrating

Grocery provides the `hydrate()` helper method for this purpose.

```php
<?php

# table fields
$foo = [
  'id' => 'primary_key',
  'bar' => 'string',
  'candy' => 'timestamp',
];

# indexed fields
$bar = ['bar', 'candy' => TRUE];

# create if not exists
isset($db['tbl']) || $db['tbl'] = $foo;

# performs the hydration
Grocery\Helpers::hydrate($db['tbl'], $foo, $bar);
```

Internally it will load the current details from the specified table,
then compare and update your table definitions against your provided changes.

Notice that some operations are restrictive depending on the driver limitations.

## Basic usage

Table access is granted through the `$db[...]` syntax, most operations can be chained.

> The last call MUST be a getter, e.g. `first()`, `pick()`, `get()` or `all()`.

```php
$tbl = $db['tbl'];
$tbl->where(['status' => 0])->count();
$tbl->where(['status' => -1])->delete();
$tbl->select('*')->where(['status' => 1])->all();
$tbl->order(['created_at' => 1])->where(['status' => 2])->select('*')->first();
$tbl->where(['id' => 42])->update(['value' => 'OSOM']);
```

## Installation

Using the composer is the best way to get installed Grocery as dependency.

```json
{
  "require": {
    "habanero/grocery": "dev-master"
  }
}
```

Then include the `vendor/autoload.php` script at the top of your project _et voila_.


## Contribute!

So far there are many ways to work with Grocery but the README is too short already.

If you want to test and write some documentation, examples, etc.
you're welcome to do pull-requests.
