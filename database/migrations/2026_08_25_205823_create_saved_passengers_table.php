<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_passengers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('full_name', 150);

            $table->string('document_number', 30)
                ->nullable();

            $table->date('date_of_birth')
                ->nullable();

            $table->enum('passenger_type_default', [
                'adult',
                'child',
                'infant',
            ]);

            $table->string('relationship', 30)
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'document_number'],
                'uq_savedpassengers_user_doc'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_passengers');
    }
};