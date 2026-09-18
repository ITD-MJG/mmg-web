<?php

it('resolves the indonesian home page at the root', function () {
    $this->get('/')->assertOk();
    expect(app()->getLocale())->toBe('id');
});

it('resolves the english home page under /en', function () {
    $this->get('/en')->assertOk();
    expect(app()->getLocale())->toBe('en');
});

it('exposes both locales in config', function () {
    expect(config('app.locales'))->toBe(['id' => '', 'en' => 'en']);
});

it('404s on an unknown locale prefix', function () {
    $this->get('/de')->assertNotFound();
});
