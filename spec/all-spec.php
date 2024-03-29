<?php

use Grocery\Base as DB;
use Grocery\Helpers as DBO;
use Grocery\Config as Config;

#Config::set('logger', function ($sql, $ms) {
#    echo "\n-- $sql\n-- $ms\n";
#});

describe('Grocery', function () {
    $datasources = array_filter([
        'sqlite::memory:',
        'sqlite::memory:#pdo',
        getenv('MY_LOCAL') ? 'mysql://root@localhost:3306/test' : '',
        getenv('MY_LOCAL') ? 'mysql://root@localhost:3306/test#pdo' : '',
        getenv('CI') ? 'mysql://mysql:mysql@localhost:33306/ci_db_test' : '',
        getenv('CI') ? 'mysql://mysql:mysql@localhost:33306/ci_db_test#pdo' : '',
        (getenv('CI') && !defined('HHVM_VERSION')) ? 'pgsql://postgres:postgres@localhost/ci_db_test' : '',
        (getenv('CI') && !defined('HHVM_VERSION')) ? 'pgsql://postgres:postgres@localhost/ci_db_test#pdo' : '',
    ]);

    $suitcase = function ($conn) {
        $db = DB::connect($conn);
        $version = json_encode($db->version());

        describe("Using $conn / $version", function () use ($db) {
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
                        $db->reset()->create('a', ['id' => DB::pk(), 'x' => DB::int(['default' => -1])]);

                        $db->a->insert(['x' => 123]);
                        $db->a->insert(['x' => 456]);

                        let('a', $db->a);
                    });

                    it('should has consistency', function ($a) {
                        expect($a->count())->toEqual(2);
                        expect($a->all())->toBeArray();
                    });

                    describe('columns', function () {
                        it('should be able to migrate', function ($a) {
                            DBO::hydrate($a, ['x' => DB::str()]);

                            $b = $a->columns();

                            expect($b['x']['type'])->toEqual('string');
                        });

                        it('should maintain consistency', function ($a) {
                            $test = $a->all();

                            expect($test[0]->x)->toEqual(123);
                            expect($test[1]->x)->toEqual(456);
                        });

                        it('can insert records', function ($a) {
                            $a->insert(['x' => 1]);
                        });

                        it('can count records', function ($a) {
                            expect(count($a->where(['x' => 1])))->toEqual(1);
                        });

                        it('can select records', function ($a, $db) {
                            expect($a->where(['x' => 1])->order_by([$db->rand()])->first()->x)->toEqual('1');
                        });

                        it('can update records', function ($a) {
                            $old = $a->where(['x' => 1])->first();
                            $old->update(['x' => 42]);

                            expect($a->where(['x' => 42])->first()->x)->toEqual('42');
                        });

                        it('can delete records', function ($a) {
                            $a->where(['x' => 42])->delete();

                            expect($a->where(['x' => 42])->count())->toEqual(0);
                        });

                        it('should add columns on extra fields', function ($a) {
                            DBO::hydrate($a, ['x' => DB::int(), 'y' => DB::int()]);
                        });

                        it('should remove columns on missing fields', function ($a) {
                            DBO::hydrate($a, ['x' => DB::int()]);
                        });
                    });

                    describe('indexes', function () {
                        it('should add indexes when they are provided', function ($a) {
                            DBO::hydrate($a, ['x' => DB::int(), 'y' => DB::int()], ['x']);

                            $test = $a->indexes();

                            expect($test['a_x___idx']['unique'])->toBeFalsy();
                        });

                        it('should set indexes as unique when passing true', function ($a) {
                            DBO::hydrate($a, ['x' => DB::int(), 'y' => DB::int()], ['x', 'y' => true]);

                            $test = $a->indexes();

                            expect($test['a_y___idx']['unique'])->toBeTruthy();
                        });

                        it('should remove indexes when they are missing', function ($a) {
                            DBO::hydrate($a, ['x' => DB::int(), 'y' => DB::int()], ['y' => false]);

                            $test = $a->indexes();

                            expect($test)->toHaveLength(1);
                        });
                    });
                });
            });
        });
    };

    array_map($suitcase, $datasources);
});
