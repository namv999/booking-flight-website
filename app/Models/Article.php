<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    // khóa => nhãn hiển thị. Thêm/bớt chuyên mục chỉ sửa ở đây
    public const CATEGORIES = [
        'travel-guide' => 'Cẩm nang du lịch',
        'promotion' => 'Khuyến mãi',
        'airline-news' => 'Tin hãng bay',
        'airport-info' => 'Thông tin sân bay',
        'booking-tips' => 'Mẹo đặt vé',
        'announcement' => 'Thông báo',
    ];

    protected $fillable = [
        'user_id', 'title', 'slug', 'excerpt', 'content', 'thumbnail_path',
        'category', 'status', 'is_featured', 'view_count', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Đã xuất bản VÀ đã tới giờ xuất bản (cho phép hẹn giờ)
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    // Ảnh seed nằm ở public/images, ảnh upload nằm ở storage/app/public
    public function hasUploadedThumbnail(): bool
    {
        return $this->thumbnail_path && ! str_starts_with($this->thumbnail_path, 'images/');
    }

    public function getThumbnailUrlAttribute(): string
    {
        if (! $this->thumbnail_path) {
            return asset('images/article-travel-guide.png');
        }

        return $this->hasUploadedThumbnail()
            ? asset('storage/'.$this->thumbnail_path)
            : asset($this->thumbnail_path);
    }

    // Markdown -> HTML. html_input=strip bỏ mọi thẻ HTML thô nên an toàn để in bằng {!! !!}
    public function getContentHtmlAttribute(): string
    {
        return Str::markdown($this->content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function deleteUploadedThumbnail(): void
    {
        if ($this->hasUploadedThumbnail()) {
            Storage::disk('public')->delete($this->thumbnail_path);
        }
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'bai-viet';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
