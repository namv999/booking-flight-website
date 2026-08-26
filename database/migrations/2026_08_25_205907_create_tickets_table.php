<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_flight_id')
                ->constrained('booking_flights')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('passenger_id')
                ->constrained('passengers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('flight_seat_id')
                ->nullable()
                ->constrained('flight_seats')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('companion_adult_passenger_id')
                ->nullable()
                ->constrained('passengers')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->boolean('is_self_selected')
                ->default(false);

            $table->foreignId('baggage_addon_id')
                ->nullable()
                ->constrained('baggage_addons')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Snapshot giá tại thời điểm đặt
            $table->decimal('price', 12, 2);

            $table->string('ticket_code', 20)
                ->unique();

            $table->timestamps();

            // Một ghế/chuyến chỉ gắn với tối đa một vé.
            // MySQL cho phép nhiều NULL trong UNIQUE.
            $table->unique(
                'flight_seat_id',
                'uq_tickets_flightseat'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};