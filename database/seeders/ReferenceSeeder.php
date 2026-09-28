<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReferenceSeeder extends Seeder
{
    /**
     * Idempotent reference data: anything the application needs to run.
     * Never contains demo users or records.
     */
    public function run(): void
    {
        $this->call([
            TicketStatusSeeder::class,
            PrioritySeeder::class,
            CategorySeeder::class,
            DepartmentSeeder::class,
            SettingsSeeder::class,
        ]);
    }
}
