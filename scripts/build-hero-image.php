<?php

/**
 * Derives the committed hero image from the untracked source photograph.
 *
 * The hero is a real photograph, and the repository ships the derived files
 * rather than the source. That split follows the principal logos: originals
 * live in `storage/app/logo-src` and the committed assets in
 * `public/images/principals`, because `storage/app/*` is gitignored and this
 * project deploys by pushing the repository. A source kept only in storage
 * would never reach the server.
 *
 * The derived files are committed so the production host needs no image
 * tooling, which is the same reason `public/build` is committed.
 *
 * Source
 * ------
 * `storage/app/hero-src/hero-source.jpg`
 *
 *   "Scientist in a laboratory Esculab" by Esculab (2021-05-26).
 *   Wikimedia Commons, CC0 1.0 (Public Domain Dedication).
 *   https://commons.wikimedia.org/wiki/File:Scientist_in_a_laboratory_Esculab.jpg
 *
 * CC0 imposes no attribution requirement, so the credit above is courtesy
 * rather than obligation. It is recorded here because a public-domain image
 * with no recorded origin is one nobody can verify or replace later.
 *
 * What this does
 * --------------
 * One 16:9 JPEG, cropped from the centre and encoded twice. The source is
 * 3:2, so a centre crop to 16:9 discards the top and bottom. That is the
 * correct axis for this photograph: the bench and the seated scientist run
 * across the middle, and the ceiling is the expendable part.
 *
 * Two files, not one. The markup declares `srcset` with the 1600 as the
 * fallback, so a browser that understands `srcset` fetches the size its
 * viewport needs and one that does not gets the 1600. Shipping only the 1600
 * would send roughly 230KB to a phone that can use 100KB.
 *
 * Idempotent: re-running produces byte-identical output, which is what makes
 * it safe to run in CI or on a whim. JPEG encoding is deterministic for a
 * fixed input and quality.
 *
 * Usage
 * -----
 *   php scripts/build-hero-image.php
 */
const SOURCE = 'storage/app/hero-src/hero-source.jpg';
const TARGET_DIR = 'public/images';

/** Aspect ratio of the hero slot. Keep in step with the `width`/`height` in the view. */
const TARGET_RATIO = 16 / 9;

/** Widths to emit: [file suffix => pixel width]. */
const WIDTHS = [
    'hero-1600' => 1600,
    'hero-800' => 800,
];

function fail(string $message): never
{
    fwrite(STDERR, "error: {$message}\n");
    exit(1);
}

/**
 * Resolve a path against the project root rather than the working directory,
 * so the script behaves the same however it is invoked.
 */
function projectPath(string $relative): string
{
    return dirname(__DIR__).'/'.$relative;
}

if (! extension_loaded('gd')) {
    fail('the gd extension is required to rebuild the hero image');
}

$source = projectPath(SOURCE);

if (! is_file($source)) {
    fail(
        "source photograph missing at {$source}.\n".
        "       It is untracked by design. The derived files are already committed,\n".
        '       so this script is only needed to change the hero, not to build it.'
    );
}

$image = imagecreatefromjpeg($source);

if ($image === false) {
    fail("could not read {$source} as a JPEG");
}

$sourceWidth = imagesx($image);
$sourceHeight = imagesy($image);

// Crop to the target ratio first, so both outputs share one framing and cannot
// drift apart. Cropping after resizing would do the same thing by a longer
// route and leave two places to get the arithmetic wrong.
$sourceRatio = $sourceWidth / $sourceHeight;

if ($sourceRatio > TARGET_RATIO) {
    // Wider than the target: keep the full height and trim the sides.
    $cropHeight = $sourceHeight;
    $cropWidth = (int) round($sourceHeight * TARGET_RATIO);
} else {
    // Taller than the target: keep the full width and trim top and bottom,
    // which is this photograph's case.
    $cropWidth = $sourceWidth;
    $cropHeight = (int) round($sourceWidth / TARGET_RATIO);
}

$cropX = (int) round(($sourceWidth - $cropWidth) / 2);
$cropY = (int) round(($sourceHeight - $cropHeight) / 2);

echo "source  {$sourceWidth}x{$sourceHeight}\n";
echo 'crop    '.$cropWidth.'x'.$cropHeight." at +{$cropX}+{$cropY}\n";

foreach (WIDTHS as $suffix => $width) {
    $height = (int) round($width / TARGET_RATIO);

    $canvas = imagecreatetruecolor($width, $height);

    // Resample from the source crop straight to the output size in one step.
    // Resizing twice would soften the result for no benefit.
    imagecopyresampled(
        $canvas,
        $image,
        0,
        0,
        $cropX,
        $cropY,
        $width,
        $height,
        $cropWidth,
        $cropHeight,
    );

    $target = projectPath(TARGET_DIR."/{$suffix}.jpg");

    // Quality 82 is the knee of the curve for this kind of image: mostly flat
    // clinical surfaces, where higher settings add bytes without visible
    // detail. The source is already a JPEG, so this is a second generation and
    // 82 keeps that from showing.
    imagejpeg($canvas, $target, 82);

    imagedestroy($canvas);

    printf("%-16s %4dx%-4d %6.1f KB\n", $suffix.'.jpg', $width, $height, filesize($target) / 1024);
}

imagedestroy($image);

echo "\nWritten to ".TARGET_DIR."\n";
