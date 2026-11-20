<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            // Xóa tài khoản tác giả thì giữ bài, chỉ bỏ liên kết
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();

            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('excerpt', 300);
            $table->longText('content');                       // Markdown
            $table->string('thumbnail_path', 255)->nullable();
            $table->string('category', 30);                    // khóa trong Article::CATEGORIES
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
