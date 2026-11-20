<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->string('type', 30);
            $table->string('title', 150);
            $table->string('message', 500);
            $table->string('url', 255)->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_broadcasts');
    }
};
