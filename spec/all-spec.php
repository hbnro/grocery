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
        it('should allow you to create tables', function() use ($db) {
          expect(function() use ($db) {
            $foo = $db->api->create('foo', array('id' => array('primary_key')));
          })->not->toThrow();

          expect(sizeof($db->api))->toBe(1);
          expect((string) $db->api['foo'])->toBe('foo');
        });

        it('should allow you to rename tables', function() use ($db) {
          $db->api->rename('foo', 'bar');
          expect(isset($db->api['foo']))->toBeFalsy();
          expect(isset($db->api['bar']))->toBeTruthy();
        });

        it('should allow you to drop tables', function() use ($db) {
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

        it('should allow you to add columns', function() use ($db) {
          $db->api->add_column('foo', 'does', 'integer');
          expect($db->api->columns('foo'))->toHaveKey('does');
        });

        it('should allow you to rename columns', function() use ($db) {
          $db->api->rename_column('foo', 'does', 'nothing');
          expect($db->api->columns('foo'))->toHaveKey('nothing');
          expect($db->api->columns('foo'))->not->toHaveKey('does');
        });

        it('should allow you to change columns', function() use ($db) {
          $db->api->change_column('foo', 'nothing', 'string');
          $tmp = $db->api->columns('foo');
          expect($tmp['nothing']['type'])->toEqual('string');
        });

        it('should allow you to delete columns', function() use ($db) {
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

        it('should allow you to add indexes', function() use ($db) {
          $db->api->add_index('foo', 'my_str', array('str'));
          expect($db->api->indexes('foo'))->toHaveKey('my_str');
        });

        it('should allow you to remove indexes', function() use ($db) {
          $db->api->remove_index('foo', 'my_str');
          expect($db->api->indexes('foo'))->not->toHaveKey('my_str');
        });
      });

      describe('Operations', function() use ($db) {
        it('should perform without issues', function() use ($db) {
          $db = $db->api;

          $col = array('str' => array('string'), 'id' => array('primary_key'));
          $set = array('foo', 'bar');

          $top = rand(3, 7);
          $max = sizeof($set) * 10;

          foreach ($set as $tbl) {
            $db->create($tbl, $col);
          }

          expect($db->tables())->toEqual($set);

          for ($i = 0; $i < $max; $i += 1) {
            $key = array_rand($set);
            $tbl = $set[$key];

            $val['str'] = str_repeat(md5(mt_rand()), $top);
            $db->insert($tbl, $val); // CREATE
          }

          $tmp = array();

          foreach ($db->tables() as $i => $one) {
            $tmp[$one] = $db->select($one)->fetch_all($i % 2); // READ
          }

          $ok = 0;

          foreach ($tmp as $tbl => $old) {
            $up = 0;
            $rm = 0;
            $del = array();
            $min = rand(1, ceil(sizeof($old) / rand(2, 9)));
            $all = $db->select($tbl, '*', array(), array('limit' => $min, 'order' => 'random'));

            while ($one = $all->fetch()) {
              $one->str = 'OK';
              $del []= $one->id;
              $up += (int) !! $db->update($tbl, $one->to_a(), array('id' => end($del))); // UPDATE
            }

            $cur = $db->result("SELECT COUNT(*) FROM $tbl WHERE str = 'OK'");

            if ($cur == $up) {
              $ok += 1;
            }

            $diff = $db->delete($tbl, array('id' => $del)); // DELETE
            $now = $db->select($tbl, 'COUNT(*)', array('str !' => 'OK'))->result();
            $test = sizeof($old) - $cur;
            $sum = $diff + $test;
            $max = sizeof($old);

            if ($sum == $max) {
              $ok += 1;
            }
          }

          expect($ok / 2)->toEqual(sizeof($set));

          foreach ($db as $one) {
            $one->drop();
          }

          expect(sizeof($db->tables()))->toEqual(0);

          $db->list->create(array('title' => 'string', 'id' => 'primary_key'));

          for ($i = 0; $i < 10; $i += 1) {
            $db->list->insert(array('title' => 'Hello World!'));
          }

          $db->list->update(array('title' => 'FUUUUUUUUU!!!'), array('id' => array(2,4,6,8)));
          $db->list->delete(array('id' => array(1,3,5,7,9)));

          $tmp = $db['list']->select()->fetch_all();
          expect(json_encode($tmp[0]))->toEqual('{"title":"FUUUUUUUUU!!!","id":2}');

          $post = array(
            'title' => 'string',
            'body' => 'text',
          ); // model

          isset($db['post']) OR $db['post'] = $post;

          $post = $db['post'];

          for ($i = 0; $i < $top; $i += 1) {
            $post->insert(array('title' => md5(uniqid(''))));
          }

          expect(count($post))->toEqual($top);
          expect($post->count())->toEqual($top);

          $post->limit(1)->each(function ($row) {
            expect($row->title)->not->toBeEmpty();
          });

          $post->delete();
          expect($post->select('COUNT(*)')->result())->toBeFalsy();

          $db->stats();
        });
      });
    });
  }, $datasources);
});
