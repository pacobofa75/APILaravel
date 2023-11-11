<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        
        if (!Role::where('nickname', 'admin')->exists()) {
            Role::create(['nickname' => 'admin']);
        }

        if (!Role::where('nickname', 'player')->exists()) {
            Role::create(['nickname' => 'player']);
        }
    }
}
?>