<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// The catalogue's search path is a MySQL FULLTEXT index, and InnoDB FULLTEXT
// cannot see uncommitted rows: a row inserted inside a transaction is not in
// the index until COMMIT, so MATCH returns nothing. RefreshDatabase wraps every
// test in a transaction, which makes FULLTEXT search untestable — the test
// would fail against a working implementation. Verified directly on the server:
// inside a transaction MATCH found 0 rows, after COMMIT it found 1.
//
// This suite therefore commits and truncates between tests instead. It lives
// outside `Feature/` because Pest refuses to rebind a test case for a nested
// directory, and it is registered in phpunit.xml as its own suite.
//
// `DatabaseTruncation` only truncates BEFORE each test, so the last test's
// committed rows are still there when the next suite starts. `Feature` uses
// `RefreshDatabase`, which skips `migrate:fresh` once `RefreshDatabaseState::
// $migrated` is true and just opens a transaction over whatever it finds, so
// those rows become visible to Feature tests that count rows. That is not
// hypothetical: it is what made `CatalogSchemaTest`'s `published()->count()`
// assertions fail once this suite's last test seeded a product. Truncating
// after each test leaves the database empty for whoever runs next.
pest()->extend(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->afterEach(function () {
        $this->truncateDatabaseTables();
    })
    ->in('Catalog');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
