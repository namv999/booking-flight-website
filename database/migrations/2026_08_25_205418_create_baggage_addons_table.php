<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('baggage_addons', function (Blueprint $table) {
            $table->id();

            $table->string('name', 50);
            $table->unsignedInteger('weight_kg');
            $table->decimal('price', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baggage_addons');
    }
};