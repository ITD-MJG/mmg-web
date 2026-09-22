<?php

namespace App\Filament\Resources\Tags\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TagsTable
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
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                // The count is the useful signal here: a tag attached to nothing
                // is a tag nobody can reach through a product, which is how a
                // stale vocabulary shows up.
                TextColumn::make('products_count')
                    ->label('Produk')
                    ->counts('products'),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('is_published')
                    ->label('Terbit')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Terbit' : 'Draf')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
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
