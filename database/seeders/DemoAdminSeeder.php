<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()
            ->where(function ($query) {
                $query
                    ->whereIn('email', ['admin@digistocki.local', 'farshid.nemati@gmail.com'])
                    ->orWhereIn('mobile', ['09121111111', '09118112618']);
            })
            ->first() ?? new User;

        $admin->forceFill([
            'name' => 'فرشید نعمتی',
            'mobile' => '09118112618',
            'email' => 'farshid.nemati@gmail.com',
            'password' => Hash::make('5p64g49'),
            'role' => 'admin',
            'is_active' => true,
        ])->save();
    }
}
