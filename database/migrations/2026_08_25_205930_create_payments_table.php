<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->decimal('amount', 12, 2);

            $table->string('method', 30);

            $table->enum('status', [
                'pending',
                'success',
                'failed',
            ])->default('pending');

            $table->string('transaction_code', 100)
                ->nullable();

            $table->dateTime('paid_at')
                ->nullable();

            $table->timestamps();

            // 1 booking <-> 1 payment
            $table->unique(
                'booking_id',
                'uq_payments_booking'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};