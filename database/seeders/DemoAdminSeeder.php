<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@digistocki.local'],
            ['name' => 'مدیر سیستم', 'mobile' => '09121111111', 'password' => Hash::make('password')],
        );

        $admin->forceFill(['role' => 'admin', 'is_active' => true])->save();
    }
}
