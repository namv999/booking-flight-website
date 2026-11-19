<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class FlightScheduleSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('flights:generate');
        $this->command?->info(trim(Artisan::output()));
    }
}
