<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

it('runs on php 8.4 or newer', function () {
    expect(version_compare(PHP_VERSION, '8.4.0', '>='))->toBeTrue();
});

it('uses mysql, because the schema needs generated columns and fulltext indexes', function () {
    expect(DB::connection()->getDriverName())->toBe('mysql');
});

it('registers the filament admin login route', function () {
    expect(Route::has('filament.admin.auth.login'))->toBeTrue();
});

it('defaults to indonesian and the jakarta timezone', function () {
    expect(config('app.locale'))->toBe('id')
        ->and(config('app.timezone'))->toBe('Asia/Jakarta');
});
