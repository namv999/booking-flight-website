<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passengers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('full_name', 150);

            $table->enum('passenger_type', [
                'adult',
                'child',
                'infant',
            ]);

            $table->string('document_number', 30)
                ->nullable();

            $table->date('date_of_birth')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passengers');
    }
};