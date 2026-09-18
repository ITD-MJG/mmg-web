<?php

namespace App\Enums;

enum CertificationType: string
{
    case IzinEdar = 'izin_edar';
    case Akl = 'akl';
    case Iso13485 = 'iso_13485';
    case Iso9001 = 'iso_9001';
    case DistributorLicence = 'distributor_licence';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::IzinEdar => 'Izin Edar',
            self::Akl => 'AKL (Alat Kesehatan Luar)',
            self::Iso13485 => 'ISO 13485',
            self::Iso9001 => 'ISO 9001',
            self::DistributorLicence => 'Surat Izin Distributor',
            self::Other => 'Lainnya',
        };
    }
}
