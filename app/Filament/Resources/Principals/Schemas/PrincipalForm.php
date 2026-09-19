<?php

namespace App\Filament\Resources\Principals\Schemas;

use App\Enums\CertificationType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class PrincipalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),

                Tabs::make('Translations')
                    ->tabs([
                        Tabs\Tab::make('Indonesia')
                            ->schema([
                                Textarea::make('description.id')
                                    ->label('Deskripsi')
                                    ->rows(3),
                            ]),
                        Tabs\Tab::make('English')
                            ->schema([
                                Textarea::make('description.en')
                                    ->label('Description')
                                    ->rows(3),
                            ]),
                    ])
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->directory('principals'),

                Repeater::make('certifications')
                    ->label('Sertifikasi / Izin Edar')
                    ->helperText('Kosongkan jika belum ada data yang boleh dipublikasikan.')
                    // The blank row the Repeater would otherwise seed fails
                    // `type`'s `required()` rule and makes an uncertified
                    // principal unsaveable. Principals without published
                    // certifications are the normal case.
                    ->defaultItems(0)
                    ->schema([
                        Select::make('type')
                            ->label('Jenis')
                            // `label()` rather than `$case->name`: staff see
                            // "Izin Edar", not "IzinEdar".
                            ->options(
                                collect(CertificationType::cases())
                                    ->mapWithKeys(fn (CertificationType $type): array => [$type->value => $type->label()])
                                    ->all()
                            )
                            ->required(),
                        // A certification often has only a type and number.
                        // Left blank, these fields would dehydrate as explicit
                        // `null`s, so every stored row would carry four null
                        // keys that the public certifications table then has to
                        // guard against.
                        TextInput::make('number')->label('Nomor')->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('issuer')->label('Penerbit')->dehydrated(fn (?string $state): bool => filled($state)),
                        DatePicker::make('valid_until')->label('Berlaku sampai')->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('url')->label('Tautan')->url()->dehydrated(fn (?string $state): bool => filled($state)),
                    ])
                    // No `mutateDehydratedStateUsing()` here: principals store
                    // `certifications` as a JSON list of rows (unlike product
                    // `specs`, which is a keyed object), and the Repeater's own
                    // `dehydrateItems()` already produces that shape.
                    ->columnSpanFull(),

                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_published')
                    ->label('Terbitkan')
                    ->default(false),
            ]);
    }
}
