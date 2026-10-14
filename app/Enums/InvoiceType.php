<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Charge = 'charge';
    case Rent = 'rent';
    case Penalty = 'penalty';
}
