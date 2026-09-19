<?php

use App\Models\Setting;

/**
 * Guards the logo contract.
 *
 * The logo is a shipped public asset referenced by a `logo` setting, not an
 * upload. Three properties matter and none is covered by the page tests: the
 * mark is used when configured, the company name remains the accessible name
 * so a screen reader is not left with a bare filename, and the mark stays
 * legible in dark mode.
 *
 * Every assertion here was mutation-tested. The first draft of the fallback
 * test asserted only that the company name appeared somewhere in the response,
 * which the `<title>` and the copyright line satisfy on their own: mutating
 * the fallback element to arbitrary text still passed. It now scopes to the
 * header, where the fallback is the only source of that string.
 */
it('renders the configured logo as an image labelled with the company name', function () {
    Setting::set('company_name', 'Medquest Mitra Global');
    Setting::set('logo', 'images/MMG-logo.png');

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('images/MMG-logo.png')
        ->toContain('alt="Medquest Mitra Global"');
});

it('falls back to the company name in the header when no logo is configured', function () {
    Setting::set('company_name', 'Medquest Mitra Global');
    Setting::set('logo', null);

    $html = $this->get('/')->assertOk()->getContent();

    // Scope to the header. Asserting against the whole page would pass on the
    // strength of the <title> and the footer copyright even if the header
    // fallback were broken, which is what the mutation check caught.
    preg_match('#<header\b.*?</header>#s', $html, $matches);
    expect($matches)->not->toBeEmpty('No <header> found in the response');
    $header = $matches[0];

    // The wordmark must render, and no <img> may point at a logo: an
    // unconfigured install would otherwise show a broken image on every page.
    expect($header)->toContain('Medquest Mitra Global');
    expect(str_contains($header, 'MMG-logo.png'))
        ->toBeFalse('header rendered a logo image while no logo setting was configured');
});

it('reserves the logo box so the header does not shift while it loads', function () {
    Setting::set('logo', 'images/MMG-logo.png');

    $html = $this->get('/')->assertOk()->getContent();

    // Intrinsic dimensions must match the shipped file (903x864). Without them
    // the header reflows when the image arrives, a visible layout shift on
    // every page load.
    expect($html)->toContain('width="903"')
        ->toContain('height="864"');
});

it('backs the logo with a plate so it stays legible in dark mode', function () {
    Setting::set('logo', 'images/MMG-logo.png');

    $html = $this->get('/')->assertOk()->getContent();

    // The logo's charcoal stroke is #393939: 10.98:1 on the light canvas but
    // 1.71:1 on the dark one, where the G would all but disappear. The plate
    // is what keeps the mark readable when the system prefers dark. It is a
    // visual property, so no functional test can catch its removal; this is
    // the only guard.
    expect($html)->toContain('bg-logo-plate');
});
