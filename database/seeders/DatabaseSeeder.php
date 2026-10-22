<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events stay enabled on purpose: they generate tree codes and
     * compute sale totals.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            MasterDataSeeder::class,
        ]);

        if (config('seeding.demo_data')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
