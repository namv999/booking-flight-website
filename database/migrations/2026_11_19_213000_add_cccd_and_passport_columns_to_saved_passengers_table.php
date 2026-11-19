<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_passengers', function (Blueprint $table) {
            $table->string('cccd_number', 30)->nullable()->after('relationship');
            $table->date('cccd_issue_date')->nullable()->after('cccd_number');
            $table->string('passport_number', 30)->nullable()->after('cccd_issue_date');
            $table->string('passport_issued_country', 100)->nullable()->after('passport_number');
            $table->date('passport_issue_date')->nullable()->after('passport_issued_country');
            $table->date('passport_expiry_date')->nullable()->after('passport_issue_date');
        });

        DB::table('saved_passengers')->where('document_type', 'national_id')->update([
            'cccd_number' => DB::raw('document_number'),
            'cccd_issue_date' => DB::raw('document_issue_date'),
        ]);

        DB::table('saved_passengers')->where('document_type', 'passport')->update([
            'passport_number' => DB::raw('document_number'),
            'passport_issued_country' => DB::raw('document_issued_country'),
            'passport_issue_date' => DB::raw('document_issue_date'),
            'passport_expiry_date' => DB::raw('document_expiry_date'),
        ]);
    }

    public function down(): void
    {
        Schema::table('saved_passengers', function (Blueprint $table) {
            $table->dropColumn([
                'cccd_number',
                'cccd_issue_date',
                'passport_number',
                'passport_issued_country',
                'passport_issue_date',
                'passport_expiry_date',
            ]);
        });
    }
};
