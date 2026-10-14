<?php

namespace App\Http\Controllers;

use App\Models\Storage;
use App\Models\StorageAssignment;

class StorageController extends AmenityController
{
    protected function cfg(): array
    {
        return [
            'model' => Storage::class, 'assignment' => StorageAssignment::class,
            'fk' => 'storage_id', 'open' => 'open_storage_id', 'prefix' => 'storages',
            'title' => 'انباری', 'plural' => 'انباری‌ها', 'declared' => 'storage_count_info',
        ];
    }
}
