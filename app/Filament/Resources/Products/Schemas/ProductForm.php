<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\CertificationType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Translations')
                    ->tabs([
                        Tabs\Tab::make('Indonesia')
                            ->schema([
                                TextInput::make('name.id')
                                    ->label('Nama')
                                    ->required(),
                                Textarea::make('short_description.id')
                                    ->label('Ringkasan (40–60 kata)')
                                    // The count is re-evaluated on every render, so
                                    // `live()` is what makes it a guide while typing
                                    // rather than a number that only appears on reload.
                                    ->live(debounce: 500)
                                    ->helperText(fn (?string $state): string => filled($state)
                                        ? str_word_count(strip_tags($state)).' kata'
                                        : 'Kosong')
                                    ->rows(3),
                                Textarea::make('description.id')
                                    ->label('Deskripsi')
                                    ->rows(8),
                            ]),
                        Tabs\Tab::make('English')
                            ->schema([
                                TextInput::make('name.en')
                                    ->label('Name')
                                    ->required(),
                                Textarea::make('short_description.en')
                                    ->label('Summary (40–60 words)')
                                    ->live(debounce: 500)
                                    ->helperText(fn (?string $state): string => filled($state)
                                        ? str_word_count(strip_tags($state)).' words'
                                        : 'Empty')
                                    ->rows(3),
                                Textarea::make('description.en')
                                    ->label('Description')
                                    ->rows(8),
                            ]),
                    ])
                    ->columnSpanFull(),

                Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name)
                    ->required(),

                // Tags are optional and multiple, unlike the single required
                // category above. A product with no tags is a valid product, so
                // the field is not `required()` and the pivot simply stays empty.
                Select::make('tags')
                    ->label('Tag')
                    ->relationship('tags', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name)
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText('Opsional. Beberapa tag boleh dipilih.'),

                Select::make('principal_id')
                    ->label('Merek')
                    ->relationship('principal', 'name')
                    ->searchable(),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('sku')
                    ->label('SKU'),

                Repeater::make('specs')
                    ->label('Spesifikasi')
                    // No default row: the Repeater's built-in blank row would be
                    // submitted as an empty `{key: null}` entry, or block the save
                    // through the `required()` rules below. The editor adds rows.
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('key')->label('Nama')->required(),
                        TextInput::make('value')->label('Nilai')->required(),
                    ])
                    ->afterStateHydrated(function (Repeater $component): void {
                        // `formatStateUsing()` cannot be used here: it *replaces*
                        // the `afterStateHydrated` hook that `Repeater::setUp()`
                        // registers for `hydrateItems()`, which is what gives each
                        // row the UUID key it needs to stay stable across
                        // reorders and deletions. Map the stored object to rows
                        // first, then let the Repeater key them.
                        $component->state(self::specsToRows($component->getRawState()));
                        $component->hydrateItems();
                    })
                    // `dehydrateStateUsing()` cannot be used either: the Repeater
                    // installs its own `mutateDehydratedStateUsing()` in
                    // `setUp()`, and that runs *after* dehydration and calls
                    // `array_values()`, which turns the keyed object back into a
                    // list. Overriding it is what keeps the stored shape an
                    // object (`{"Ukuran": "10x15cm"}`), which the public specs
                    // table and the JSON-LD `additionalProperty` node both need.
                    ->mutateDehydratedStateUsing(fn (?array $state): array => self::rowsToSpecs($state))
                    ->columnSpanFull(),

                Repeater::make('certifications')
                    ->label('Sertifikasi / Izin Edar')
                    ->helperText('Kosongkan jika belum ada data yang boleh dipublikasikan.')
                    // An empty certifications repeater is the normal case: the
                    // company has not yet confirmed what may be published. Its
                    // blank default row would fail `type`'s `required()` rule and
                    // make the product unsaveable, so no row is seeded.
                    ->defaultItems(0)
                    ->schema([
                        Select::make('type')
                            ->label('Jenis')
                            ->options(
                                collect(CertificationType::cases())
                                    ->mapWithKeys(fn (CertificationType $type): array => [$type->value => $type->label()])
                                    ->all()
                            )
                            ->required(),
                        TextInput::make('number')->label('Nomor'),
                        TextInput::make('issuer')->label('Penerbit'),
                        DatePicker::make('valid_until')->label('Berlaku sampai'),
                        TextInput::make('url')->label('Tautan')->url(),
                    ])
                    ->columnSpanFull(),

                Toggle::make('is_published')
                    ->label('Terbitkan')
                    ->default(false),

                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0),
            ]);
    }

    /**
     * `specs` is stored as a JSON object (`{"Ukuran": "10x15cm"}`) because the
     * public specs table and the JSON-LD `additionalProperty` node both key off
     * the label, but a Repeater only understands a list of rows. This maps the
     * stored shape into rows on the way in.
     *
     * Rows that are already row-shaped are passed through untouched: the
     * Repeater hydrates a blank default row (an empty array) for a new product,
     * and treating that blank row as a key/value pair would write the row's
     * generated key into the `key` input.
     */
    private static function specsToRows(?array $state): array
    {
        $state ??= [];

        if (array_is_list($state) && collect($state)->every(fn ($row): bool => is_array($row))) {
            return $state;
        }

        return collect($state)
            ->map(fn ($value, $key): array => ['key' => $key, 'value' => $value])
            ->values()
            ->all();
    }

    /**
     * The reverse of {@see specsToRows()}: rows back to `{key: value}`. Rows
     * whose label was left blank are dropped rather than stored under an empty
     * key, so a half-filled row cannot corrupt the object.
     */
    private static function rowsToSpecs(?array $state): array
    {
        return collect($state ?? [])
            ->filter(fn ($row): bool => is_array($row) && filled($row['key'] ?? null))
            ->mapWithKeys(fn (array $row): array => [$row['key'] => $row['value'] ?? null])
            ->all();
    }
}
