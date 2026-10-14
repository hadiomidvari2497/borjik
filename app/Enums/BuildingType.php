<?php

namespace App\Enums;

enum BuildingType: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';
    case Office = 'office';
    case Educational = 'educational';
    case Mixed = 'mixed';
    case Other = 'other';
}
