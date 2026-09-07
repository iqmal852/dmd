<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\HasLabel;

enum Direction: string implements HasLabel
{
    case Northbound = 'northbound';
    case Southbound = 'southbound';
    case Eastbound = 'eastbound';
    case Westbound = 'westbound';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Northbound => 'Northbound',
            self::Southbound => 'Southbound',
            self::Eastbound => 'Eastbound',
            self::Westbound => 'Westbound',
            self::Both => 'Both',
        };
    }
}
