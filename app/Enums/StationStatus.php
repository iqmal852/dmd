<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\HasColor;
use App\Contracts\HasLabel;

enum StationStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Damaged = 'damaged';
    case Destroyed = 'destroyed';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Damaged => 'Damaged',
            self::Destroyed => 'Destroyed',
            self::Pending => 'Pending',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'accent',
            self::Inactive => 'ink-muted',
            self::Damaged, self::Destroyed => 'danger',
            self::Pending => 'warning',
        };
    }
}
