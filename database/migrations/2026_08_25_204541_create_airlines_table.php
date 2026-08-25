<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airlines', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('code', 10)->unique();
            $table->string('logo_url', 500)->nullable();
            $table->string('country', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airlines');
    }
};