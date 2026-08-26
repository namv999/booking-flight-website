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
        FlightSeeder::class,
        FlightSeatSeeder::class,
        BookingSeeder::class,
        BookingFlightSeeder::class,
        PassengerSeeder::class,
        SavedPassengerSeeder::class,
        TicketSeeder::class,
        BookingTotalSeeder::class,
        PaymentSeeder::class,
    ]);
}
}