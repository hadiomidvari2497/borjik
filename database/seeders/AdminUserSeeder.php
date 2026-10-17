<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** ادمین پیش‌فرض سیستم — پس از اولین ورود رمز را عوض کنید. */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'مدیر سیستم',
                'password' => Hash::make('admin123'),
                'status' => 'active',
                'is_super' => true,
                'two_factor_enabled' => false,
            ]
        );
    }
}
