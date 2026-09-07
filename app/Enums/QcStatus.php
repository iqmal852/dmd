<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\HasColor;
use App\Contracts\HasLabel;

enum QcStatus: string implements HasColor, HasLabel
{
    case Verified = 'verified';
    case Pending = 'pending';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Verified => 'Verified',
            self::Pending => 'Pending',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Verified => 'accent',
            self::Pending => 'warning',
            self::Rejected => 'danger',
        };
    }
}
