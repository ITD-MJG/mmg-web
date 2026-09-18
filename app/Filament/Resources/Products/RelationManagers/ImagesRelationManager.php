<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Foto';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // `path` is a plain string column, and FileUpload stores a single
                // path by default (it only produces an array when `multiple()`),
                // so the string reaches the column unchanged.
                //
                // The disk is set explicitly because the app's
                // `FILESYSTEM_DISK` is `local` (storage/app/private), while spec
                // §5.4 requires product images under storage/app/public where
                // `storage:link` can serve them.
                FileUpload::make('path')
                    ->label('Berkas')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('products')
                    ->required(),
                Toggle::make('is_cover')
                    ->label('Foto utama')
                    // Demotion of the previous cover is `ProductImage`'s `saving`
                    // hook's job; unsetting siblings here would duplicate it and
                    // race with the unique index.
                    ->helperText('Menyalakan ini akan mematikan foto utama lainnya.'),
                TextInput::make('alt.id')->label('Alt text (ID)'),
                TextInput::make('alt.en')->label('Alt text (EN)'),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('path')
            ->columns([
                ImageColumn::make('path')
                    ->label('Foto')
                    ->disk('public'),
                IconColumn::make('is_cover')
                    ->label('Utama')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric(),
            ])
            ->filters([
                //
            ])
            // Images belong to the product they were uploaded for; there is no
            // shared pool to associate from, so the generated Associate and
            // Dissociate actions are deliberately absent.
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order');
    }
}
