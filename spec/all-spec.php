<?php

describe('Grocery', function() {
  $db_file = __DIR__.DIRECTORY_SEPARATOR.'db.sqlite';

  @touch($db_file);

  $datasources = [
    'sqlite::memory:',
    'sqlite:'.$db_file,
    'sqlite:'.$db_file.'#pdo',
  ];

  before(function() use ($db_file) {
    @touch($db_file);
  });

  after(function() use ($db_file) {
    @unlink($db_file);
  });

  array_map(function($conn) {
    $db = new \stdClass();
    $db->conn = $conn;

    describe("Using $conn", function() use (&$db) {
      it('should connect without issues', function() use (&$db) {
        expect(function() use (&$db) {
          $db->api = Grocery\Base::connect($db->conn);
        })->not->toThrow();
      });

      it('should allows you to create tables', function() use (&$db) {
        expect(function() use (&$db) {
          $foo = $db->api->create('foo', array('str' => array('string'), 'id' => array('primary_key')));
        })->not->toThrow();

        expect(sizeof($db->api))->toBe(1);
        expect((string) $db->api['foo'])->toBe('foo');
      });

      it('should allows you to drop tables', function() use (&$db) {
        expect(function() use (&$db) {
          $foo = $db->api->drop('foo');
        })->not->toThrow();

        expect(sizeof($db->api))->toBe(0);
      });
    });
  }, $datasources);

  // // connection singletons


  // $col = array('str' => array('string'), 'id' => array('primary_key'));
  // $set = array('foo', 'bar');

  // $top = rand(3, 7);
  // $max = sizeof($set) * 10;

  // $db->reset();

  // it('...', function() use ($db_file) {
  //   var_dump(is_file($db_file));
  // });
});
