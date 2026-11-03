<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'role_id' => 1,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'zip_code' => '123456',
            'profile_image' => null,
            'email_verified_at' => now(),
            'password' => Hash::make('sR123456'),
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,  
        ]);
    }
}
