<?php

describe('Grocery', function() {
  $ds = require __DIR__.'/datasources.php';
  $ds(function($db, $conn) {
    let('db', $db);

    describe("Using $conn", function() {
      describe('Base', function() {
        xit('TODO', function($db) {});
      });

      describe('Config', function() {
        xit('TODO', function($db) {});
      });

      describe('Database', function() {
        describe('Debug', function() {
          xit('TODO', function($db) {});
        });

        describe('Forge', function() {
          xit('TODO', function($db) {});
        });

        describe('Scheme', function() {
          xit('TODO', function($db) {});
        });

        describe('SQL', function() {
          describe('Base', function() {
            xit('TODO', function($db) {});
          });

          describe('Dump', function() {
            xit('TODO', function($db) {});
          });

          describe('Query', function() {
            xit('TODO', function($db) {});
          });

          describe('Scheme', function() {
            xit('TODO', function($db) {});
          });
        });
      });

      describe('Handle', function() {
        describe('Base', function() {
          xit('TODO', function($db) {});
        });

        describe('Finder', function() {
          xit('TODO', function($db) {});
        });

        describe('Hasher', function() {
          xit('TODO', function($db) {});
        });

        describe('Record', function() {
          xit('TODO', function($db) {});
        });

        describe('Result', function() {
          xit('TODO', function($db) {});
        });

        describe('Table', function() {
          xit('TODO', function($db) {});
        });
      });

      describe('Helpers', function() {
        xit('TODO', function($db) {});
      });
    });
  });
});
