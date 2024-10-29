<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::where('name', 'admin')->firstOrCreate(['name' => 'admin']);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'godiahmoses70@gmail.com',
            'password' => bcrypt('admin2024'),
        ]);

        $superAdmin->roles()->attach($adminRole);
    }
}
