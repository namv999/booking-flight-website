<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('airlines', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('airports', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('aircrafts', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('fare_classes', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('baggage_addons', fn (Blueprint $table) => $table->softDeletes());
    }

    public function down(): void
    {
        Schema::table('airlines', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('airports', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('aircrafts', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('fare_classes', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('baggage_addons', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
