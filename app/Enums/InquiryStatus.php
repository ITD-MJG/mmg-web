<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Replied = 'replied';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::Read => 'Dibaca',
            self::Replied => 'Dibalas',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Read => 'info',
            self::Replied => 'success',
        };
    }
}
