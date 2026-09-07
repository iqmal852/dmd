<?php

declare(strict_types=1);

namespace App\Enums;

enum AccessMode: string
{
    case Public = 'public';
    case Password = 'password';
}
