<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CategoryForm
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
                                Textarea::make('description.id')
                                    ->label('Deskripsi')
                                    ->rows(3),
                            ]),
                        Tabs\Tab::make('English')
                            ->schema([
                                TextInput::make('name.en')
                                    ->label('Name')
                                    ->required(),
                                Textarea::make('description.en')
                                    ->label('Description')
                                    ->rows(3),
                            ]),
                    ])
                    ->columnSpanFull(),

                Select::make('parent_id')
                    ->label('Induk')
                    ->relationship('parent', 'slug')
                    ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->name)
                    ->searchable()
                    ->preload()
                    ->default(null)
                    // A category cannot be its own parent. `parent_id` is a
                    // self-referencing foreign key, so the database accepts the
                    // cycle; only a rule keyed to the edited record can reject
                    // it. Returning `null` on create (no record yet) leaves the
                    // field with no extra rule, which is the correct behaviour
                    // for a new category.
                    ->rule(fn (?Model $record): ?string => $record ? 'not_in:'.$record->getKey() : null),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                FileUpload::make('image')
                    ->label('Gambar')
                    ->image()
                    ->directory('categories'),

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
