<?php

namespace App\Enums;

enum RoleKey: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Security = 'security';
    case Cleaning = 'cleaning';
    case Maintenance = 'maintenance';
    case UnitOwner = 'unit_owner';
    case Tenant = 'tenant';
}
