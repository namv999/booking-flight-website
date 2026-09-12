<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Danh sách các bảng nghiệp vụ cần thêm Soft Deletes
        $tables = [
            'aircrafts', 'airlines', 'airports', 'baggage_addons', 
            'bookings', 'booking_flights', 'fare_classes', 'flights', 
            'flight_seats', 'passengers', 'payments', 'saved_passengers', 
            'seats', 'tickets', 'users'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'aircrafts', 'airlines', 'airports', 'baggage_addons', 
            'bookings', 'booking_flights', 'fare_classes', 'flights', 
            'flight_seats', 'passengers', 'payments', 'saved_passengers', 
            'seats', 'tickets', 'users'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};