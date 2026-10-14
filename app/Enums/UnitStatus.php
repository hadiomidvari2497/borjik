<?php

namespace App\Enums;

enum UnitStatus: string
{
    case Sold = 'sold';
    case Rented = 'rented';
    case Vacant = 'vacant';
    case Repair = 'repair';
}
