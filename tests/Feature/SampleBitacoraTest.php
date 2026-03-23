<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use App\Enums\SampleEventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SampleBitacoraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function issueTokenForRole(string $role): string
    {
        $user = User::role($role)->first();
        return JWTAuth::fromUser($user);
    }

    #[Test]
    public function sample_creation_logs_created_event()
    {
        $token = $this->issueTokenForRole('admin');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();

        $this->withBearerToken($token)
            ->postJson('/api/v1/samples', [
                'project_id' => $project->id,
                'code' => 'SAMPLE-001',
                'priority' => 'standard',
                'received_at' => '2026-03-17',
            ]);

        $this->assertDatabaseHas('sample_events', [
            'event_type' => SampleEventType::CREATED->value,
            'description' => 'Sample created',
        ]);
    }

    #[Test]
    public function status_change_logs_event_with_old_and_new()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create(['status' => 'pending']);

        $this->withBearerToken($token)
            ->patchJson("/api/v1/samples/{$sample->id}/status", [
                'status' => 'in_progress',
            ]);

        $this->assertDatabaseHas('sample_events', [
            'sample_id' => $sample->id,
            'event_type' => SampleEventType::STATUS_CHANGED->value,
            'old_status' => 'pending',
            'new_status' => 'in_progress',
        ]);
    }

    #[Test]
    public function priority_change_logs_event_with_old_and_new()
    {
        $token = $this->issueTokenForRole('admin');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create(['priority' => 'standard']);

        $this->withBearerToken($token)
            ->patchJson("/api/v1/samples/{$sample->id}/priority", [
                'priority' => 'urgent',
            ]);

        $this->assertDatabaseHas('sample_events', [
            'sample_id' => $sample->id,
            'event_type' => SampleEventType::PRIORITY_CHANGED->value,
            'old_priority' => 'standard',
            'new_priority' => 'urgent',
        ]);
    }

    #[Test]
    public function status_to_in_progress_logs_analysis_started()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create(['status' => 'pending']);

        $this->withBearerToken($token)
            ->patchJson("/api/v1/samples/{$sample->id}/status", [
                'status' => 'in_progress',
            ]);

        $this->assertDatabaseHas('sample_events', [
            'sample_id' => $sample->id,
            'event_type' => SampleEventType::ANALYSIS_STARTED->value,
        ]);
    }

    #[Test]
    public function status_to_completed_logs_completed_event()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create(['status' => 'in_progress']);

        $this->withBearerToken($token)
            ->patchJson("/api/v1/samples/{$sample->id}/status", [
                'status' => 'completed',
            ]);

        $this->assertDatabaseHas('sample_events', [
            'sample_id' => $sample->id,
            'event_type' => SampleEventType::COMPLETED->value,
            'description' => 'Sample completed',
        ]);
    }

    #[Test]
    public function result_addition_logs_result_added_event()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $this->withBearerToken($token)
            ->postJson("/api/v1/samples/{$sample->id}/results", [
                'result_summary' => 'Test result',
            ]);

        $this->assertDatabaseHas('sample_events', [
            'sample_id' => $sample->id,
            'event_type' => SampleEventType::RESULT_ADDED->value,
            'description' => 'Result added',
        ]);
    }

    #[Test]
    public function sample_update_logs_updated_event()
    {
        $token = $this->issueTokenForRole('admin');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $this->withBearerToken($token)
            ->putJson("/api/v1/samples/{$sample->id}", [
                'notes' => 'Updated',
            ]);

        $this->assertDatabaseHas('sample_events', [
            'sample_id' => $sample->id,
            'event_type' => SampleEventType::UPDATED->value,
            'description' => 'Sample updated',
        ]);
    }

    #[Test]
    public function sample_deletion_logs_deleted_event()
    {
        $token = $this->issueTokenForRole('admin');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $this->withBearerToken($token)
            ->deleteJson("/api/v1/samples/{$sample->id}");

        $this->assertDatabaseHas('sample_events', [
            'sample_id' => $sample->id,
            'event_type' => SampleEventType::DELETED->value,
            'description' => 'Sample deleted',
        ]);
    }

    #[Test]
    public function sample_restoration_logs_restored_event()
    {
        $token = $this->issueTokenForRole('admin');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();
        $sample->delete();

        $this->withBearerToken($token)
            ->postJson("/api/v1/samples/{$sample->id}/restore");

        $this->assertDatabaseHas('sample_events', [
            'sample_id' => $sample->id,
            'event_type' => SampleEventType::RESTORED->value,
            'description' => 'Sample restored',
        ]);
    }

    #[Test]
    public function analysis_started_sets_timestamp()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create(['status' => 'pending']);

        $this->withBearerToken($token)
            ->patchJson("/api/v1/samples/{$sample->id}/status", [
                'status' => 'in_progress',
            ]);

        $sample->refresh();
        $this->assertNotNull($sample->analysis_started_at);
    }

    #[Test]
    public function completion_sets_timestamp()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create(['status' => 'in_progress']);

        $this->withBearerToken($token)
            ->patchJson("/api/v1/samples/{$sample->id}/status", [
                'status' => 'completed',
            ]);

        $sample->refresh();
        $this->assertNotNull($sample->completed_at);
    }

    #[Test]
    public function events_ordered_by_created_at_descending()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $this->withBearerToken($token)
            ->patchJson("/api/v1/samples/{$sample->id}/status", [
                'status' => 'in_progress',
            ])
            ->assertStatus(200);

        $this->withBearerToken($token)
            ->patchJson("/api/v1/samples/{$sample->id}/status", [
                'status' => 'completed',
            ])
            ->assertStatus(200);

        $response = $this->withBearerToken($token)
            ->getJson("/api/v1/samples/{$sample->id}/events");

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);

        $data = $response->json('data.items');
        $this->assertGreaterThanOrEqual(2, count($data));

        for ($i = 0; $i < count($data) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                strtotime($data[$i + 1]['created_at']),
                strtotime($data[$i]['created_at'])
            );
        }
    }
}
