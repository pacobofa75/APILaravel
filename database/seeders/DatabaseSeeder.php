<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();
        $this->call(RoleSeeder::class);

        $userAdmin = User::create([
            'nickname' => 'Paco',
            'email' => 'pacodebofa@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt(123456789),
            'remember_token' => Str::random(10),
        ]);

        $userAdmin->assignRole('admin');

        $userPlayer = User::create([
            'nickname' => 'Leo',
            'email' => 'leodebofa@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt(987654321),
            'remember_token' => Str::random(10),
        ]);

        $userPlayer->assignRole('player');


    }
}
