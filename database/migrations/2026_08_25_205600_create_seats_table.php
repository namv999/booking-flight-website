<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('aircraft_id')
                ->constrained('aircrafts')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('seat_number', 10);

            $table->enum('seat_class', [
                'economy',
                'business',
                'first',
            ]);

            $table->enum('position', [
                'window',
                'aisle',
                'middle',
            ])->nullable();

            $table->timestamps();

            $table->unique(
                ['aircraft_id', 'seat_number'],
                'uq_seats_aircraft_number'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};