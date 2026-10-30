<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);

        // Sample events, attendees and payments are only for local work. They
        // are never planted on a production database.
        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
