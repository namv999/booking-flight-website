<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['passengers', 'saved_passengers'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->enum('gender', ['male', 'female'])->nullable()->after('date_of_birth');
                $t->date('document_issue_date')->nullable()->after('document_issued_country');
            });
        }
    }

    public function down(): void
    {
        foreach (['passengers', 'saved_passengers'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['gender', 'document_issue_date']);
            });
        }
    }
};
