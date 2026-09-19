<?php

namespace App\Support;

class Navigation
{
    /**
     * The primary navigation links for a locale, as route names.
     *
     * Shared by the header and the footer so the two cannot drift: adding a
     * page means adding one entry here, not editing two partials. Labels are
     * translation keys, resolved in the view.
     *
     * @return list<array{label: string, route: string}>
     */
    public static function links(string $locale): array
    {
        return [
            ['label' => 'ui.nav.products', 'route' => "{$locale}.products.index"],
            ['label' => 'ui.nav.principals', 'route' => "{$locale}.principals.index"],
            ['label' => 'ui.nav.about', 'route' => "{$locale}.about"],
            ['label' => 'ui.nav.contact', 'route' => "{$locale}.contact"],
        ];
    }
}
