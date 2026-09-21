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

it('frames the logo at 16:9 rather than leaving it at the file ratio', function () {
    Setting::set('logo', 'images/MMG-logo.png');

    $html = $this->get('/')->assertOk()->getContent();

    // The frame is the wrapper, not the image. This is not a style preference:
    // `aspect-video` on the `<img>` produced a 387x48 box (an 8:1 ratio) because
    // `w-auto` on a replaced element resolves the width from the file's own
    // intrinsic ratio (903x864) rather than from the `aspect-ratio` property.
    // A non-replaced wrapper has no intrinsic ratio to compete with. So this
    // test targets the wrapper, and the measured ratio in the browser is what
    // confirmed the wrapper approach actually works.
    preg_match('#<span class="flex [^"]*aspect-video[^"]*">\s*<img[^>]*MMG-logo\.png[^>]*>#s', $html, $frame);
    expect($frame)->not->toBeEmpty('the logo image is not wrapped in an aspect-video frame');

    $frameTag = $frame[0];

    // `aspect-video` is 16:9 and `object-contain` centres the near-square mark
    // inside it, so the mark is not stretched by the frame. On a 16:9 box it is
    // height-constrained: the frame's height decides the mark's size and the
    // surplus width is empty space that a wide wordmark would occupy later.
    expect($frameTag)->toContain('aspect-video');

    // The image fills the frame; that is what `object-contain` then centres
    // within. `h-full w-full` on the image is correct and required.
    preg_match('#<img[^>]*>#s', $frameTag, $img);
    expect($img[0])->toContain('h-full')
        ->toContain('w-full')
        ->toContain('object-contain');

    // No width on the *frame*. The caller passes a height and the width follows
    // from 16:9; a width class would fight the ratio.
    preg_match('/class="([^"]*)"/', $frameTag, $frameClasses);
    expect((bool) preg_match('/\bw-/', $frameClasses[1]))
        ->toBeFalse("the logo frame sets a width ({$frameClasses[1]}), which fights aspect-video");
});

it('sizes the logo frame larger than the previous fixed height', function () {
    Setting::set('logo', 'images/MMG-logo.png');

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#<header\b.*?</header>#s', $html, $header);
    preg_match('#<footer\b.*?</footer>#s', $html, $footer);
    expect($header)->not->toBeEmpty('No <header> found');
    expect($footer)->not->toBeEmpty('No <footer> found');

    // The frame heights, pinned. These are the enlarged values: the header was
    // 2.25rem, then 3rem, and is now 4rem; the footer was 3rem and is now 5rem.
    // On a 16:9 frame the height is the mark's size, so a revert to any earlier
    // value is a visible regression. A class name is a weak thing to assert on,
    // but the alternative is measuring pixels, and there is no browser in this
    // suite.
    expect($header[0])->toContain('h-16');
    expect($footer[0])->toContain('h-20');

    // Every logo frame on the page carries the ratio, header and footer alike.
    // Counting rather than asserting presence catches a call site that was
    // missed when the partial gained the frame.
    expect(substr_count($html, 'aspect-video'))->toBeGreaterThanOrEqual(2);
});

it('removes the logo background in the default theme', function () {
    Setting::set('logo', 'images/MMG-logo.png');

    $html = $this->get('/')->assertOk()->getContent();

    // The plate was invisible against the near-white header but read as a plain
    // white rectangle in the footer, which sits on `bg-surface-muted`
    // (#f4f3f0). The mark now sits directly on whatever surface it is on.
    //
    // Matched with a negative lookbehind so `dark:bg-logo-plate` does not
    // satisfy it: the whole point is that the *unprefixed* utility is gone.
    expect((bool) preg_match('/(?<!dark:)bg-logo-plate/', $html))
        ->toBeFalse('the logo still carries an unconditional background plate');
});

it('keeps a plate in dark mode so the mark stays legible there', function () {
    Setting::set('logo', 'images/MMG-logo.png');

    $html = $this->get('/')->assertOk()->getContent();

    // The logo's charcoal stroke is #393939: 10.98:1 on the light canvas but
    // 1.71:1 on the dark one, where the G would all but disappear. Light is
    // the default and nothing sets `.dark`, so this does not affect the site as
    // it ships; it keeps the dark palette usable for when a toggle is added.
    expect($html)->toContain('dark:bg-logo-plate');
});

it('ships a transparent logo file, so no background can leak back in', function () {
    $path = public_path('images/MMG-logo.png');

    expect(is_file($path))->toBeTrue('the shipped logo is missing');

    $info = getimagesize($path);

    // IMAGETYPE_PNG is 3. A JPEG or a flattened export would be a different
    // type, and would carry its own background the moment the plate was
    // removed. Note that `getimagesize` does NOT report a channel count, which
    // is why this is a type check and the alpha is probed directly below.
    expect($info[2])->toBe(IMAGETYPE_PNG, 'the logo is not a PNG');

    $image = imagecreatefrompng($path);
    expect($image)->not->toBeFalse();

    $width = imagesx($image);
    $height = imagesy($image);

    // GD packs alpha into the high byte: 0 is opaque, 127 fully transparent.
    $alphaAt = function (int $x, int $y) use ($image): int {
        return (imagecolorat($image, $x, $y) >> 24) & 0x7F;
    };

    // The background is the corners. All four must be fully transparent, or the
    // file is painting a rectangle that the removed plate used to hide.
    foreach ([[0, 0], [$width - 1, 0], [0, $height - 1], [$width - 1, $height - 1]] as [$x, $y]) {
        expect($alphaAt($x, $y))->toBe(127, "the logo is opaque at {$x},{$y}");
    }

    // And the mark itself must be opaque, or "fully transparent everywhere"
    // would pass the corner check while rendering nothing. The centre of the
    // file is inside the mark.
    expect($alphaAt(intdiv($width, 2), intdiv($height, 2)))->toBe(0, 'the logo is transparent at its centre');

    imagedestroy($image);
});
