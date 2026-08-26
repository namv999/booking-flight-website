<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fare_classes', function (Blueprint $table) {
            $table->id();

            $table->string('name', 50);
            $table->decimal('base_price', 12, 2);
            $table->decimal('seat_selection_fee', 10, 2)
                ->default(0);

            $table->unsignedInteger('checked_baggage_kg')
                ->default(0);

            $table->unsignedInteger('carry_on_baggage_kg')
                ->default(7);

            $table->string('description', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fare_classes');
    }
};