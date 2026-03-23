<?php

namespace Database\Factories;

use App\Enums\SamplePriority;
use App\Enums\SampleStatus;
use App\Models\Project;
use App\Models\Sample;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sample>
 */
class SampleFactory extends Factory
{
    protected $model = Sample::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'code' => fake()->unique()->bothify('SAMPLE-###'),
            'status' => SampleStatus::PENDING->value,
            'priority' => SamplePriority::STANDARD->value,
            'received_at' => fake()->date(),
            'analysis_started_at' => null,
            'completed_at' => null,
            'notes' => fake()->optional()->paragraph(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
