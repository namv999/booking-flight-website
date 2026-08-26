<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airports', function (Blueprint $table) {
            $table->id();

            $table->string('iata_code', 10)->unique();
            $table->string('name', 200);
            $table->string('city', 100);
            $table->string('country', 100);
            $table->string('timezone', 50);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airports');
    }
};