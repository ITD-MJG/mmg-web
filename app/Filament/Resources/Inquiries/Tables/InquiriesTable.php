<?php

namespace App\Filament\Resources\Inquiries\Tables;

use App\Enums\InquiryStatus;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('company')
                    ->label('Perusahaan')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Telepon'),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (InquiryStatus $state): string => $state->label())
                    ->color(fn (InquiryStatus $state): string => $state->color()),
                TextColumn::make('created_at')
                    ->label('Masuk')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(InquiryStatus::cases())
                        ->mapWithKeys(fn (InquiryStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                Filter::make('created_at')
                    ->label('Rentang tanggal')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari'),
                        DatePicker::make('until')
                            ->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                        )),
            ])
            ->recordActions([
                ViewAction::make(),
                self::markReadAction(),
                self::markRepliedAction(),
            ]);
    }

    /**
     * Only offered while the inquiry is still new, so the transition cannot be
     * replayed against a record that already moved on.
     */
    private static function markReadAction(): Action
    {
        return Action::make('markRead')
            ->label('Tandai dibaca')
            ->visible(fn ($record): bool => $record->status === InquiryStatus::New)
            ->action(fn ($record) => $record->update(['status' => InquiryStatus::Read]));
    }

    /**
     * `new` and `read` both lead to replied; an already-replied inquiry has no
     * further transition.
     */
    private static function markRepliedAction(): Action
    {
        return Action::make('markReplied')
            ->label('Tandai dibalas')
            ->visible(fn ($record): bool => $record->status !== InquiryStatus::Replied)
            ->action(fn ($record) => $record->update(['status' => InquiryStatus::Replied]));
    }
}
