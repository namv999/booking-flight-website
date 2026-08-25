<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();

            $table->foreignId('aircraft_id')
                ->constrained('aircrafts')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('departure_airport_id')
                ->constrained('airports')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('arrival_airport_id')
                ->constrained('airports')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // UTC
            $table->dateTime('departure_time');
            $table->dateTime('arrival_time');

            $table->enum('status', [
                'scheduled',
                'delayed',
                'cancelled',
            ])->default('scheduled');

            $table->timestamps();

            $table->index(
                'departure_time',
                'idx_flights_departure_time'
            );

            // $table->check(
            //     'departure_airport_id <> arrival_airport_id',
            //     'ck_flights_diff_airport'
            // );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};