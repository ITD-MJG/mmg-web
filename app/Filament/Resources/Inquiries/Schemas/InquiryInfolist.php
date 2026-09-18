<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nama'),
                TextEntry::make('company')
                    ->label('Perusahaan')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email'),
                TextEntry::make('phone')
                    ->label('Telepon'),
                TextEntry::make('product.name')
                    ->label('Produk')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge(),
                TextEntry::make('locale')
                    ->label('Bahasa'),
                TextEntry::make('source_path')
                    ->label('Halaman asal')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label('Masuk')
                    ->dateTime(),
                TextEntry::make('message')
                    ->label('Pesan')
                    ->columnSpanFull(),
            ]);
    }
}
