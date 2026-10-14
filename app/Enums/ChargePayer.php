<?php

namespace App\Enums;

enum ChargePayer: string
{
    case Tenant = 'tenant';
    case Owner = 'owner';
}
