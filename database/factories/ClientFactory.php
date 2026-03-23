<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'location' => fake()->city(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
