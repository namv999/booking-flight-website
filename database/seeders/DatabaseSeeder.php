<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ArticleSeeder::class, // cần UserSeeder chạy trước để có tác giả
            AirlineSeeder::class,
            AirportSeeder::class,
            FareClassSeeder::class,
            BaggageAddonSeeder::class,
            AircraftSeeder::class,
            SeatSeeder::class,
            FlightScheduleSeeder::class,
            BookingSeeder::class,
            BookingFlightSeeder::class,
            PassengerSeeder::class,
            SavedPassengerSeeder::class,
            TicketSeeder::class,
            BookingTotalSeeder::class,
            PaymentSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
