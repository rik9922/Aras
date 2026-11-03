<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientFeedbackDepartmentsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['SLED need to send', 'SLED sent', 'SLED denied'] as $name) {
            if (! DB::table('event_departments')->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])->exists()) {
                DB::table('event_departments')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
