<?php

namespace App\Filament\Resources\ProductCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    // One slug is shared across locales and `name` is a JSON
                    // column, so search targets the per-locale JSON paths rather
                    // than the column as a whole.
                    ->searchable(['name->id', 'name->en']),
                TextColumn::make('parent.name')
                    ->label('Induk')
                    ->placeholder('—'),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                ImageColumn::make('image')
                    ->label('Gambar'),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('is_published')
                    ->label('Terbit')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Terbit' : 'Draf')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Induk')
                    ->relationship('parent', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name)
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_published')
                    ->label('Terbit'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order');
    }
}
