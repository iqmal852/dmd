<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\HasLabel;

enum PhotoType: string implements HasLabel
{
    case EyeLevel = 'eye_level';
    case TopDown = 'top_down';
    case CloseUp = 'close_up';
    case Context = 'context';

    /**
     * Default captions, taken verbatim from Picture1.png panel 4.
     */
    public function label(): string
    {
        return match ($this) {
            self::EyeLevel => 'Eye-Level Approach',
            self::TopDown => 'Top-Down (Sky Visibility)',
            self::CloseUp => 'Close-Up (Monument)',
            self::Context => 'Site Context',
        };
    }
}
