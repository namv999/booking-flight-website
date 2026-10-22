<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passport_update_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('passenger_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->cascadeOnUpdate(); // người gửi yêu cầu
            $table->json('new_data');                       // dữ liệu hộ chiếu mới khách gửi (đã chuẩn hóa)
            $table->json('old_data')->nullable();           // chụp dữ liệu cũ của hành khách LÚC DUYỆT
            $table->string('reason', 500);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->string('admin_note', 500)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['passenger_id', 'status'], 'idx_pur_passenger_status');
            $table->index(['booking_id', 'status'], 'idx_pur_booking_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_update_requests');
    }
};
