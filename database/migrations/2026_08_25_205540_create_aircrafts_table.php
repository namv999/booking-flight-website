<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aircrafts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('airline_id')
                ->constrained('airlines')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('model', 100);
            $table->string('registration_number', 20)->nullable();
            $table->unsignedInteger('total_seats');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aircrafts');
    }
};