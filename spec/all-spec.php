<?php

describe('Grocery', function() {
  $datasources = array(
    'sqlite::memory:'
  );

  array_map(function($conn) {
    $db = new \stdClass();
    $db->conn = $conn;

    describe("Using $conn", function() use ($db) {
      it('should connect without issues', function() use ($db) {
        expect(function() use ($db) {
          $db->api = Grocery\Base::connect($db->conn);
        })->not->toThrow();
      });

      describe('Tables', function() use ($db) {
        it('should allows you to create tables', function() use ($db) {
          expect(function() use ($db) {
            $foo = $db->api->create('foo', array('id' => array('primary_key')));
          })->not->toThrow();

          expect(sizeof($db->api))->toBe(1);
          expect((string) $db->api['foo'])->toBe('foo');
        });

        it('should allows you to rename tables', function() use ($db) {
          $db->api->rename('foo', 'bar');
          expect(isset($db->api['foo']))->toBeFalsy();
          expect(isset($db->api['bar']))->toBeTruthy();
        });

        it('should allows you to drop tables', function() use ($db) {
          expect(function() use ($db) {
            $db->api->drop('bar');
          })->not->toThrow();

          expect(sizeof($db->api))->toBe(0);
        });
      });

      describe('Columns', function() use ($db) {
        before(function() use ($db) {
          $db->api->create('foo', array('str' => array('string'), 'id' => array('primary_key')));
        });

        after(function() use ($db) {
          $db->api->drop('foo');
        });

        it('should allows you to add columns', function() use ($db) {
          $db->api->add_column('foo', 'does', 'integer');
          expect($db->api->columns('foo'))->toHaveKey('does');
        });

        it('should allows you to rename columns', function() use ($db) {
          $db->api->rename_column('foo', 'does', 'nothing');
          expect($db->api->columns('foo'))->toHaveKey('nothing');
          expect($db->api->columns('foo'))->not->toHaveKey('does');
        });

        it('should allows you to change columns', function() use ($db) {
          $db->api->change_column('foo', 'nothing', 'string');
          $tmp = $db->api->columns('foo');
          expect($tmp['nothing']['type'])->toEqual('string');
        });

        it('should allows you to delete columns', function() use ($db) {
          $db->api->remove_column('foo', 'nothing');
          expect($db->api->columns('foo'))->toHaveKey('str');
          expect($db->api->columns('foo'))->not->toHaveKey('nothing');
        });
      });

      describe('Indexes', function() use ($db) {
        before(function() use ($db) {
          $db->api->create('foo', array('str' => array('string'), 'id' => array('primary_key')));
        });

        after(function() use ($db) {
          $db->api->drop('foo');
        });

        it('should allows you to add indexes', function() use ($db) {
          $db->api->add_index('foo', 'my_str', array('str'));
          expect($db->api->indexes('foo'))->toHaveKey('my_str');
        });

        it('should allows you to remove indexes', function() use ($db) {
          $db->api->remove_index('foo', 'my_str');
          expect($db->api->indexes('foo'))->not->toHaveKey('my_str');
        });
      });
    });
  }, $datasources);
});
