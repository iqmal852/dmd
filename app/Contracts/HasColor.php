<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Implemented by enums rendered as a coloured pill (StationStatus, QcStatus).
 * color() returns a design token name from resources/css/app.css's @theme block
 * (e.g. 'accent', 'warning', 'danger') — never a raw hex value.
 */
interface HasColor
{
    public function color(): string;
}
