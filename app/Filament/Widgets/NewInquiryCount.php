<?php

namespace App\Filament\Widgets;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NewInquiryCount extends StatsOverviewWidget
{
    /**
     * Security: the `editor` role is denied the inquiry resource entirely, so
     * it must not be told inquiry activity exists either. The count is an
     * aggregate rather than row-level PII, but showing it to the one excluded
     * role would leak that inquiries are arriving and make the dashboard
     * disagree with the resource.
     */
    public static function canView(): bool
    {
        return Filament::auth()->user()?->hasRole('admin') ?? false;
    }

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
