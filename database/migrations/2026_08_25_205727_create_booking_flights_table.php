<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_flights', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('flight_id')
                ->constrained('flights')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->unique(
                ['booking_id', 'flight_id'],
                'uq_bookingflights'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_flights');
    }
};