<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\HasLabel;

enum DocumentType: string implements HasLabel
{
    case AsBuilt = 'as_built';
    case Report = 'report';
    case Certificate = 'certificate';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::AsBuilt => 'As-Built Drawing',
            self::Report => 'Report',
            self::Certificate => 'Certificate',
            self::Other => 'Other',
        };
    }
}
