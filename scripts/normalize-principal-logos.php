<?php

/*
|--------------------------------------------------------------------------
| Normalise the principal logos into 1:1 frames
|--------------------------------------------------------------------------
|
| The home page renders each principal mark inside a square frame. That only
| works if every file is actually square, because a logo file is not resized
| by CSS: `object-contain` fits the whole image inside the box, so a 9:1
| wordmark in a square frame leaves most of the frame empty and the wordmark
| itself tiny.
|
| This script is the reason the files are square, and it is committed rather
| than run once by hand so the transformation is reproducible. It is
| idempotent: running it over its own output changes nothing, because a
| trimmed square scaled to the same box lands back on the same pixels.
|
| Per file it does three things:
|
|   1. Trims to the bounding box of visible pixels. The sourced marks carry
|      uneven transparent padding, and leaving it in place would make the
|      marks sit at different sizes inside frames of the same size.
|   2. Scales that content to fit a square box, preserving aspect ratio. A
|      wide wordmark is limited by width and a tall mark by height; neither
|      is stretched, so no brand's proportions are altered.
|   3. Centres it on a transparent square canvas of the output size.
|
| The padding left around the content is deliberate: a mark that touched the
| frame's edge would read as cropped rather than placed.
|
| Requires the GD extension. Run from the project root:
|
|     php scripts/normalize-principal-logos.php
|
*/

const OUTPUT_SIZE = 512;

/** Fraction of the frame the mark may occupy. The rest is breathing room. */
const CONTENT_RATIO = 0.88;

/** Alpha at or below this is treated as empty when finding the bounding box. */
const ALPHA_FLOOR = 4;

$directory = dirname(__DIR__).'/public/images/principals';

if (! is_dir($directory)) {
    fwrite(STDERR, "No such directory: {$directory}\n");
    exit(1);
}

/**
 * Bounding box of the pixels that are not effectively transparent.
 *
 * Returns null for an image that is entirely transparent, which is a broken
 * asset rather than a logo; the caller reports it rather than guessing.
 *
 * @return array{int, int, int, int}|null [x, y, width, height]
 */
function contentBounds(GdImage $image): ?array
{
    $width = imagesx($image);
    $height = imagesy($image);

    $minX = $width;
    $minY = $height;
    $maxX = -1;
    $maxY = -1;

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $alpha = imagecolorsforindex($image, imagecolorat($image, $x, $y))['alpha'];

            if ($alpha > ALPHA_FLOOR) {
                continue;
            }

            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
        }
    }

    if ($maxX < $minX || $maxY < $minY) {
        return null;
    }

    return [$minX, $minY, $maxX - $minX + 1, $maxY - $minY + 1];
}

$files = glob($directory.'/*.png');
sort($files);

$processed = 0;
$failures = [];

foreach ($files as $file) {
    $name = basename($file);
    $source = @imagecreatefrompng($file);

    if ($source === false) {
        $failures[] = "{$name}: not a readable PNG";

        continue;
    }

    $bounds = contentBounds($source);

    if ($bounds === null) {
        $failures[] = "{$name}: fully transparent, nothing to frame";
        imagedestroy($source);

        continue;
    }

    [$sx, $sy, $sw, $sh] = $bounds;

    // Fit inside the content box without distorting: the limiting dimension
    // decides the scale.
    $box = (int) round(OUTPUT_SIZE * CONTENT_RATIO);
    $scale = min($box / $sw, $box / $sh);
    $dw = max(1, (int) round($sw * $scale));
    $dh = max(1, (int) round($sh * $scale));

    $canvas = imagecreatetruecolor(OUTPUT_SIZE, OUTPUT_SIZE);

    // Blending off on both sides of the copy so the source alpha is carried
    // over as alpha. With it on, semi-transparent edge pixels would be
    // composited against black and every mark would gain a dark halo.
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

    imagecopyresampled(
        $canvas,
        $source,
        (int) round((OUTPUT_SIZE - $dw) / 2),
        (int) round((OUTPUT_SIZE - $dh) / 2),
        $sx,
        $sy,
        $dw,
        $dh,
        $sw,
        $sh,
    );

    imagesavealpha($canvas, true);
    imagepng($canvas, $file, 9);

    imagedestroy($canvas);
    imagedestroy($source);

    printf("%-20s %4dx%-4d -> %dx%d\n", $name, $sw, $sh, OUTPUT_SIZE, OUTPUT_SIZE);
    $processed++;
}

echo "\n{$processed} file(s) normalised to ".OUTPUT_SIZE.'x'.OUTPUT_SIZE.".\n";

if ($failures !== []) {
    fwrite(STDERR, "\n".count($failures)." failure(s):\n  ".implode("\n  ", $failures)."\n");
    exit(1);
}
