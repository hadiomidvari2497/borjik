<?php

namespace App\Enums;

enum PeriodStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Published = 'published';
}
