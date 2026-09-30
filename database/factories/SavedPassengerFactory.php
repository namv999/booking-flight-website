<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedPassengerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'document_type' => null,
            'document_number' => null,
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'nationality' => null,
            'document_issued_country' => null,
            'document_expiry_date' => null,
            'passenger_type_default' => 'adult',
            'relationship' => null,
        ];
    }
}
