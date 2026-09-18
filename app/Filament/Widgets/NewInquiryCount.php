<?php

namespace App\Filament\Widgets;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NewInquiryCount extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $count = Inquiry::query()->where('status', InquiryStatus::New)->count();

        return [
            Stat::make('Permintaan baru', $count)
                ->description('Belum dibaca')
                ->color($count > 0 ? 'warning' : 'success'),
        ];
    }
}
