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
pest()->extend(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Catalog');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
