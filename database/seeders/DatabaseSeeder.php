<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference data is always seeded (idempotently). Demo data is only
     * seeded in local/testing environments.
     */
    public function run(): void
    {
        $this->call([
            ReferenceSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call([
                DemoSeeder::class,
            ]);
        }
    }
}
