<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SampleApiTest extends TestCase
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
    public function can_list_samples()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        Sample::factory(3)->for($project)->create();

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/samples');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ok',
            'data' => [
                'items' => [[
                    'id', 'code', 'status', 'priority', 'project_id', 'project_name',
                    'client_id', 'client_name', 'received_at', 'latest_result_summary',
                    'latest_result_at', 'results_count', 'created_by_name', 'updated_at',
                ]],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ],
            'message',
        ]);
    }

    #[Test]
    public function admin_can_create_sample()
    {
        $token = $this->issueTokenForRole('admin');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();

        $response = $this->withBearerToken($token)
            ->postJson('/api/v1/samples', [
                'project_id' => $project->id,
                'code' => 'SAMPLE-001',
                'priority' => 'standard',
                'received_at' => '2026-03-17',
                'notes' => 'Test sample',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('message', 'Sample created successfully.');
        $response->assertJsonFragment(['code' => 'SAMPLE-001', 'status' => 'pending']);
        $this->assertDatabaseHas('samples', ['code' => 'SAMPLE-001']);
    }

    #[Test]
    public function cannot_reuse_code_from_soft_deleted_sample()
    {
        $token = $this->issueTokenForRole('admin');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();

        $sample = Sample::factory()->for($project)->create([
            'code' => 'SAMPLE-LOCKED-001',
        ]);
        $sample->delete();

        $response = $this->withBearerToken($token)
            ->postJson('/api/v1/samples', [
                'project_id' => $project->id,
                'code' => 'SAMPLE-LOCKED-001',
                'priority' => 'standard',
                'received_at' => '2026-03-17',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $response->assertJsonPath('error.details.code.0', 'The code has already been taken.');
    }

    #[Test]
    public function analyst_cannot_create_sample()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();

        $response = $this->withBearerToken($token)
            ->postJson('/api/v1/samples', [
                'project_id' => $project->id,
                'code' => 'SAMPLE-001',
                'priority' => 'standard',
                'received_at' => '2026-03-17',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function can_show_sample()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $response = $this->withBearerToken($token)
            ->getJson("/api/v1/samples/{$sample->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $response->assertJsonFragment(['id' => $sample->id]);
    }

    #[Test]
    public function cannot_show_soft_deleted_sample()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();
        $sample->delete();

        $response = $this->withBearerToken($token)
            ->getJson("/api/v1/samples/{$sample->id}");

        $response->assertStatus(404);
    }

    #[Test]
    public function can_update_sample_notes()
    {
        $token = $this->issueTokenForRole('admin');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $response = $this->withBearerToken($token)
            ->putJson("/api/v1/samples/{$sample->id}", [
                'notes' => 'Updated notes',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'notes' => 'Updated notes']);
    }

    #[Test]
    public function analyst_cannot_update_sample()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $response = $this->withBearerToken($token)
            ->putJson("/api/v1/samples/{$sample->id}", ['notes' => 'Updated']);

        $response->assertStatus(403);
    }

    #[Test]
    public function analyst_can_change_status()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $response = $this->withBearerToken($token)
            ->patchJson("/api/v1/samples/{$sample->id}/status", [
                'status' => 'in_progress',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'status' => 'in_progress']);
    }

    #[Test]
    public function analyst_cannot_change_priority()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $response = $this->withBearerToken($token)
            ->patchJson("/api/v1/samples/{$sample->id}/priority", [
                'priority' => 'urgent',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_change_priority()
    {
        $token = $this->issueTokenForRole('admin');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $response = $this->withBearerToken($token)
            ->patchJson("/api/v1/samples/{$sample->id}/priority", [
                'priority' => 'urgent',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'priority' => 'urgent']);
    }

    #[Test]
    public function analyst_can_add_result()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $response = $this->withBearerToken($token)
            ->postJson("/api/v1/samples/{$sample->id}/results", [
                'result_summary' => 'Test result',
                'result_data' => ['key' => 'value'],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $this->assertDatabaseHas('sample_results', [
            'sample_id' => $sample->id,
            'analyst_id' => auth()->id(),
        ]);
    }

    #[Test]
    public function admin_can_delete_sample()
    {
        $token = $this->issueTokenForRole('admin');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $response = $this->withBearerToken($token)
            ->deleteJson("/api/v1/samples/{$sample->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('message', 'Sample deleted successfully.');
        $this->assertSoftDeleted('samples', ['id' => $sample->id]);
    }

    #[Test]
    public function analyst_cannot_delete_sample()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();

        $response = $this->withBearerToken($token)
            ->deleteJson("/api/v1/samples/{$sample->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_restore_sample()
    {
        $token = $this->issueTokenForRole('admin');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();
        $sample->delete();

        $response = $this->withBearerToken($token)
            ->postJson("/api/v1/samples/{$sample->id}/restore");

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $this->assertDatabaseHas('samples', ['id' => $sample->id, 'deleted_at' => null]);
    }

    #[Test]
    public function analyst_cannot_restore_sample()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();
        $sample->delete();

        $response = $this->withBearerToken($token)
            ->postJson("/api/v1/samples/{$sample->id}/restore");

        $response->assertStatus(403);
    }

    #[Test]
    public function can_filter_by_status()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        Sample::factory()->for($project)->create(['status' => 'pending']);
        Sample::factory()->for($project)->create(['status' => 'in_progress']);

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/samples?status=pending');

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $this->assertTrue(
            collect($response->json('data.items'))->every(fn ($item) => $item['status'] === 'pending')
        );
    }

    #[Test]
    public function can_filter_by_priority()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        Sample::factory()->for($project)->create(['priority' => 'standard']);
        Sample::factory()->for($project)->create(['priority' => 'urgent']);

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/samples?priority=urgent');

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $this->assertTrue(
            collect($response->json('data.items'))->every(fn ($item) => $item['priority'] === 'urgent')
        );
    }

    #[Test]
    public function can_filter_by_project()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project1 = Project::factory()->for($client)->create();
        $project2 = Project::factory()->for($client)->create();
        Sample::factory()->for($project1)->create();
        Sample::factory()->for($project2)->create();

        $response = $this->withBearerToken($token)
            ->getJson("/api/v1/samples?project_id={$project1->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $this->assertTrue(
            collect($response->json('data.items'))->every(fn ($item) => $item['project_id'] === $project1->id)
        );
    }

    #[Test]
    public function can_filter_by_date_range()
    {
        $token = $this->issueTokenForRole('analyst');
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        Sample::factory()->for($project)->create(['received_at' => '2026-03-01']);
        Sample::factory()->for($project)->create(['received_at' => '2026-03-20']);

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/samples?received_from=2026-03-15&received_to=2026-03-25');

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $this->assertEquals(1, count($response->json('data.items')));
    }

    #[Test]
    public function can_get_events()
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

        $response = $this->withBearerToken($token)
            ->getJson("/api/v1/samples/{$sample->id}/events");

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $response->assertJsonStructure([
            'data' => [
                'items' => [['id', 'event_type', 'description', 'user_name', 'created_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ],
        ]);
    }
}
