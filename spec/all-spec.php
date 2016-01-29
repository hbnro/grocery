<?php

describe('Grocery', function () {
  $ds = require __DIR__.'/datasources.php';
  $ds(function ($db, $conn) {
    describe("Using $conn", function () use ($db) {
      let('db', $db->reset());

      // describe('Base', function () {
      //   xit('TODO', function ($db) {});
      // });

      // describe('Database', function () {
      //   describe('Debug', function () {
      //     xit('TODO', function ($db) {});
      //   });

      //   describe('Forge', function () {
      //     xit('TODO', function ($db) {});
      //   });

      //   describe('Scheme', function () {
      //     xit('TODO', function ($db) {});
      //   });

      //   describe('SQL', function () {
      //     describe('Base', function () {
      //       xit('TODO', function ($db) {});
      //     });

      //     describe('Dump', function () {
      //       xit('TODO', function ($db) {});
      //     });

      //     describe('Query', function () {
      //       xit('TODO', function ($db) {});
      //     });

      //     describe('Scheme', function () {
      //       xit('TODO', function ($db) {});
      //     });
      //   });
      // });

      // describe('Handle', function () {
      //   describe('Base', function () {
      //     xit('TODO', function ($db) {});
      //   });

      //   describe('Finder', function () {
      //     xit('TODO', function ($db) {});
      //   });

      //   describe('Hasher', function () {
      //     xit('TODO', function ($db) {});
      //   });

      //   describe('Record', function () {
      //     xit('TODO', function ($db) {});
      //   });

      //   describe('Result', function () {
      //     xit('TODO', function ($db) {});
      //   });

      //   describe('Table', function () {
      //     xit('TODO', function ($db) {});
      //   });
      // });

      describe('Helpers', function () {
        describe('hydrate()', function () {
          before(function ($db) {
            $db->reset()->create('a', array('id' => 'primary_key', 'x' => 'integer'));

            $db->a->insert(array('x' => 123));
            $db->a->insert(array('x' => 456));

            let('a', $db->a);
          });

          it('should has consistency', function ($a) {
            expect($a->count())->toEqual(2);
            expect($a->all())->toBeArray();
          });

          describe('columns', function () {
            it('should be able to migrate', function ($a) {
              \Grocery\Helpers::hydrate($a, array('x' => 'string'));

              $b = $a->columns();

              expect($b['x']['type'])->toEqual('string');
            });

            it('should maintain consistency', function ($a) {
              expect($a->all()[0]->x)->toEqual(123);
              expect($a->all()[1]->x)->toEqual(456);
            });

            it('should add columns on extra fields', function ($a) {
              \Grocery\Helpers::hydrate($a, array('x' => 'integer', 'y' => 'integer'));
            });

            it('should remove columns on missing fields', function ($a) {
              \Grocery\Helpers::hydrate($a, array('x' => 'integer'));
            });
          });

          describe('indexes', function() {
            it('should add indexes when they are provided', function ($a) {
              \Grocery\Helpers::hydrate($a, array('x' => 'integer', 'y' => 'integer'), array('x'));
              expect($a->indexes()['a_x___idx']['unique'])->toBeFalsy();
            });

            it('should set indexes as unique when passing TRUE', function ($a) {
              \Grocery\Helpers::hydrate($a, array('x' => 'integer', 'y' => 'integer'), array('x', 'y' => TRUE));
              expect($a->indexes()['a_y___idx']['unique'])->toBeTruthy();
            });

            it('should remove indexes when they are missing', function ($a) {
              \Grocery\Helpers::hydrate($a, array('x' => 'integer', 'y' => 'integer'), array('y' => FALSE));
              expect($a->indexes())->toHaveLength(1);
            });
          });
        });
      });
    });
  });
});
