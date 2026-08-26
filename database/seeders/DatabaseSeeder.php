<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AirlineSeeder::class,
            AirportSeeder::class,
            BaggageAddonSeeder::class,
            FareClassSeeder::class,
            AircraftSeeder::class,
            SeatSeeder::class,
        ]);
    }
}