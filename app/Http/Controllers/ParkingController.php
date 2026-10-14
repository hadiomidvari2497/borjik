<?php

namespace App\Http\Controllers;

use App\Models\Parking;
use App\Models\ParkingAssignment;

class ParkingController extends AmenityController
{
    protected function cfg(): array
    {
        return [
            'model' => Parking::class, 'assignment' => ParkingAssignment::class,
            'fk' => 'parking_id', 'open' => 'open_parking_id', 'prefix' => 'parkings',
            'title' => 'پارکینگ', 'plural' => 'پارکینگ‌ها', 'declared' => 'parking_count_info',
        ];
    }
}
