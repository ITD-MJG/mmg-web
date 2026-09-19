<?php

/**
 * Flatten a nested translation array to a sorted list of dot-notation keys.
 *
 * @return list<string>
 */
function uiTranslationKeys(string $locale): array
{
    $path = lang_path("{$locale}/ui.php");

    expect(file_exists($path))->toBeTrue("Missing translation file: lang/{$locale}/ui.php");

    $flatten = function (array $items, string $prefix = '') use (&$flatten): array {
        $keys = [];

        foreach ($items as $key => $value) {
            $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $keys = array_merge($keys, $flatten($value, $full));

                continue;
            }

            $keys[] = $full;
        }

        return $keys;
    };

    $keys = $flatten(require $path);

    sort($keys);

    return $keys;
}

it('keeps the id and en ui translation files in exact key parity', function () {
    // fallback_locale is `id`, so a key present only in lang/id/ui.php would
    // silently render Indonesian on every /en page. Asserting the sorted key
    // lists are identical turns that silent degradation into a failure.
    expect(uiTranslationKeys('en'))->toBe(uiTranslationKeys('id'));
});

it('defines a non-empty string for every ui key in both locales', function () {
    foreach (['id', 'en'] as $locale) {
        foreach (require lang_path("{$locale}/ui.php") as $section => $values) {
            expect($values)->toBeArray("ui.{$section} must be a section array in {$locale}");

            foreach ($values as $key => $value) {
                expect($value)
                    ->toBeString()
                    ->not->toBe('', "ui.{$section}.{$key} is empty in {$locale}");
            }
        }
    }
});
