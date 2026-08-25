<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_seats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('flight_id')
                ->constrained('flights')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('seat_id')
                ->constrained('seats')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('fare_class_id')
                ->constrained('fare_classes')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->decimal('price', 12, 2);

            $table->enum('status', [
                'available',
                'held',
                'booked',
            ])->default('available');

            $table->foreignId('held_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('held_until')->nullable();

            $table->timestamps();

            $table->unique(
                ['flight_id', 'seat_id'],
                'uq_flightseats_flight_seat'
            );

            $table->index(
                ['flight_id', 'status'],
                'idx_flightseats_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_seats');
    }
};