<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(6), '.');

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 999999),
            'excerpt' => fake()->sentence(15),
            'content' => '## '.fake()->sentence(4)."\n\n".fake()->paragraph(4),
            'thumbnail_path' => null,
            'category' => fake()->randomElement(array_keys(Article::CATEGORIES)),
            'status' => 'published',
            'is_featured' => false,
            'view_count' => 0,
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
