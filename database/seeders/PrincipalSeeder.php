<?php

namespace Database\Seeders;

use App\Models\Principal;
use Illuminate\Database\Seeder;

/**
 * The manufacturers MMG distributes for.
 *
 * Sourced from the sales system's principal register, which is the list the
 * business actually trades on, rather than the placeholder set the catalog was
 * scaffolded with. The two lists do not overlap: the scaffold carried eight
 * well-known brands and the register carries the twenty-six the company really
 * represents.
 *
 * Mindray is the one name kept from the scaffold. Six published products
 * (uMEC12, SP-750, BeneHeart D6, DC-40, SV-300, SP-50) reference it through
 * `products.principal_id`, so removing it would leave six live products with
 * no brand at all. The remaining seven scaffold names are unreferenced and are
 * removed.
 *
 * "Peky Bio" and "Pekybio" are the same company entered twice in the register;
 * the register's own item rows spell it "Peky Bio", so that is the name kept.
 *
 * Logos ship as public assets rather than uploads. `storage/app/public` is
 * gitignored and this project deploys by pushing the repository, so an
 * uploaded file would never reach the server. Each is a transparent PNG
 * trimmed to its content and centred in a 1:1 frame, so the carousel can give
 * every mark the same square box regardless of whether the source was a wide
 * wordmark or a square badge. `scripts/normalize-principal-logos.php` is what
 * produces those frames and is committed so the transformation is repeatable.
 * Biochem is the one brand whose mark could not be sourced; it renders as its
 * name instead.
 *
 * Re-runnable: rows are matched on `slug`, so running the seeder again
 * refreshes names, order, and logos in place and leaves `products.principal_id`
 * intact.
 */
class PrincipalSeeder extends Seeder
{
    /**
     * Ordered as the register lists them. Mindray is appended last because it
     * is not part of that register.
     *
     * @var list<array{name: string, slug: string, logo: string|null}>
     */
    private const PRINCIPALS = [
        ['name' => 'Infitek', 'slug' => 'infitek', 'logo' => 'infitek'],
        ['name' => 'Arctiko', 'slug' => 'arctiko', 'logo' => 'arctiko'],
        ['name' => 'AFI', 'slug' => 'afi', 'logo' => 'afi'],
        ['name' => 'Young In Ace', 'slug' => 'young-in-ace', 'logo' => 'young-in-ace'],
        // No published mark found for this brand. It falls back to its name
        // rather than shipping a logo that belongs to someone else.
        ['name' => 'Biochem', 'slug' => 'biochem', 'logo' => null],
        ['name' => 'Biocomma', 'slug' => 'biocomma', 'logo' => 'biocomma'],
        ['name' => 'Coolfinity', 'slug' => 'coolfinity', 'logo' => 'coolfinity'],
        ['name' => 'FL Medical', 'slug' => 'fl-medical', 'logo' => 'fl-medical'],
        ['name' => 'Oxford', 'slug' => 'oxford', 'logo' => 'oxford'],
        ['name' => 'Monmouth', 'slug' => 'monmouth', 'logo' => 'monmouth'],
        ['name' => 'Genolution', 'slug' => 'genolution', 'logo' => 'genolution'],
        ['name' => 'Genesystem', 'slug' => 'genesystem', 'logo' => 'genesystem'],
        ['name' => 'Peky Bio', 'slug' => 'pekybio', 'logo' => 'pekybio'],
        ['name' => 'Solis Biodyne', 'slug' => 'solis-biodyne', 'logo' => 'solis-biodyne'],
        ['name' => 'Tecan', 'slug' => 'tecan', 'logo' => 'tecan'],
        ['name' => 'Diapro', 'slug' => 'diapro', 'logo' => 'diapro'],
        ['name' => 'Diaspect', 'slug' => 'diaspect', 'logo' => 'diaspect'],
        ['name' => 'Nodford', 'slug' => 'nodford', 'logo' => 'nodford'],
        ['name' => 'Mandelab', 'slug' => 'mandelab', 'logo' => 'mandelab'],
        ['name' => 'Sysmex', 'slug' => 'sysmex', 'logo' => 'sysmex'],
        ['name' => 'Micronic', 'slug' => 'micronic', 'logo' => 'micronic'],
        ['name' => 'Abbott', 'slug' => 'abbott', 'logo' => 'abbott'],
        ['name' => 'BT Lab', 'slug' => 'bt-lab', 'logo' => 'bt-lab'],
        ['name' => 'Lumira', 'slug' => 'lumira', 'logo' => 'lumira'],
        ['name' => 'Biomerieux', 'slug' => 'biomerieux', 'logo' => 'biomerieux'],
        ['name' => 'Mindray', 'slug' => 'mindray', 'logo' => 'mindray'],
    ];

    public function run(): void
    {
        foreach (self::PRINCIPALS as $order => $principal) {
            Principal::updateOrCreate(
                ['slug' => $principal['slug']],
                [
                    'name' => $principal['name'],
                    'logo' => $principal['logo'] === null
                        ? null
                        : "images/principals/{$principal['logo']}.png",
                    'sort_order' => $order,
                    'is_published' => true,
                ],
            );
        }

        // Remove the scaffold names this register replaces, named explicitly
        // rather than expressed as "anything not in the list above". A
        // `whereNotIn` would also delete principals an admin adds through the
        // panel on the next deploy, which is not this seeder's business.
        //
        // Mindray is deliberately absent: it is kept, and the six published
        // products that reference it would be orphaned without it.
        Principal::whereIn('slug', [
            'philips',
            'ge-healthcare',
            'siemens-healthineers',
            'onemed',
            'nihon-kohden',
            'b-braun',
            'terumo',
        ])->delete();
    }
}
