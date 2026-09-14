<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        User::updateOrCreate(['email' => 'test@mail.ru'], [
            'name' => 'Max',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}
