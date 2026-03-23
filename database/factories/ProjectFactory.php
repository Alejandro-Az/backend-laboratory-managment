<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->sentence(3),
            'status' => ProjectStatus::ACTIVE->value,
            'started_at' => fake()->date(),
            'ended_at' => null,
            'description' => fake()->optional()->paragraph(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
