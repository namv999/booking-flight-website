<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'pending',
            'total_amount' => 0,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'paid']);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'cancelled']);
    }

    // Test expiry cần created_at trong quá khứ - forceFill vì mass assignment
    // protection chặn ->update(['created_at'=>...]) (đã note trong schema-and-decisions)
    public function createdMinutesAgo(int $minutes): static
    {
        return $this->state(fn (array $attrs) => [
            'created_at' => now()->subMinutes($minutes),
        ]);
    }
}
