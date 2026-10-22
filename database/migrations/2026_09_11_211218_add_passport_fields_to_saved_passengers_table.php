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
        Schema::table('saved_passengers', function (Blueprint $table) {
            $table->enum('document_type', ['national_id', 'passport'])->nullable()->after('document_number');
            $table->string('nationality', 100)->nullable()->after('document_type');
            $table->string('document_issued_country', 100)->nullable()->after('nationality');
            $table->date('document_expiry_date')->nullable()->after('document_issued_country');
        });
    }

    public function down(): void
    {
        Schema::table('saved_passengers', function (Blueprint $table) {
            $table->dropColumn(['document_type', 'nationality', 'document_issued_country', 'document_expiry_date']);
        });
    }
};
