<?php

namespace Tests\Feature;

use App\Enums\SampleEventType;
use App\Models\Client;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleEvent;
use App\Models\SampleResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DashboardApiTest extends TestCase
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
    public function admin_can_view_metrics(): void
    {
        $token = $this->issueTokenForRole('admin');

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/metrics');

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'Dashboard metrics retrieved successfully.');
    }

    #[Test]
    public function analyst_can_view_metrics(): void
    {
        $token = $this->issueTokenForRole('analyst');

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/metrics');

        $response->assertOk()->assertJsonPath('ok', true);
    }

    #[Test]
    public function user_without_token_cannot_access_dashboard_endpoints(): void
    {
        $response = $this->getJson('/api/v1/dashboard/metrics');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    #[Test]
    public function metrics_returns_expected_shape(): void
    {
        $token = $this->issueTokenForRole('admin');

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/metrics');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'total_samples',
                    'urgent_samples',
                    'pending_analysis',
                    'completion_rate',
                ],
                'message',
            ]);
    }

    #[Test]
    public function completion_rate_is_zero_when_no_samples_exist(): void
    {
        $token = $this->issueTokenForRole('admin');

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/metrics');

        $response
            ->assertOk()
            ->assertJsonPath('data.total_samples', 0)
            ->assertJsonPath('data.completion_rate', 0);
    }

    #[Test]
    public function pending_analysis_counts_pending_and_in_progress_only(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();

        Sample::factory()->for($project)->create(['status' => 'pending']);
        Sample::factory()->for($project)->create(['status' => 'in_progress']);
        Sample::factory()->for($project)->create(['status' => 'completed']);
        Sample::factory()->for($project)->create(['status' => 'cancelled']);

        $token = $this->issueTokenForRole('admin');

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/metrics');

        $response
            ->assertOk()
            ->assertJsonPath('data.pending_analysis', 2);
    }

    #[Test]
    public function urgent_samples_counts_only_urgent_priority(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();

        Sample::factory()->for($project)->create(['priority' => 'urgent']);
        Sample::factory()->for($project)->create(['priority' => 'urgent']);
        Sample::factory()->for($project)->create(['priority' => 'standard']);

        $token = $this->issueTokenForRole('admin');

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/metrics');

        $response
            ->assertOk()
            ->assertJsonPath('data.urgent_samples', 2)
            ->assertJsonPath('data.total_samples', 3);
    }

    #[Test]
    public function recent_samples_excludes_soft_deleted_samples(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();

        $activeSample = Sample::factory()->for($project)->create(['code' => 'ACTIVE-001']);
        $deletedSample = Sample::factory()->for($project)->create(['code' => 'DELETED-001']);
        $deletedSample->delete();

        $token = $this->issueTokenForRole('admin');

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/recent-samples');

        $response
            ->assertOk()
            ->assertJsonPath('data.meta.count', 1)
            ->assertJsonMissingPath('data.meta.total')
            ->assertJsonMissingPath('data.meta.limit')
            ->assertJsonMissingPath('data.meta.has_more');

        $codes = collect($response->json('data.items'))->pluck('code')->all();

        $this->assertContains($activeSample->code, $codes);
        $this->assertNotContains($deletedSample->code, $codes);
    }

    #[Test]
    public function recent_samples_returns_project_and_client_fields(): void
    {
        $client = Client::factory()->create(['name' => 'BioTech Corp']);
        $project = Project::factory()->for($client)->create(['name' => 'Vaccine Development']);
        $sample = Sample::factory()->for($project)->create(['code' => 'SMP-2026-100']);

        SampleResult::create([
            'sample_id' => $sample->id,
            'analyst_id' => User::factory()->create()->id,
            'result_summary' => 'Within expected range',
            'result_data' => ['ph' => 7.1],
        ]);

        $token = $this->issueTokenForRole('admin');

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/recent-samples');

        $response
            ->assertOk()
            ->assertJsonPath('data.items.0.project_name', 'Vaccine Development')
            ->assertJsonPath('data.items.0.client_name', 'BioTech Corp')
            ->assertJsonPath('data.items.0.latest_result_summary', 'Within expected range');
    }

    #[Test]
    public function recent_samples_is_ordered_by_created_at_descending(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();

        $olderSample = Sample::factory()->for($project)->create([
            'code' => 'SMP-OLDER',
            'created_at' => now()->subMinute(),
        ]);

        $newerSample = Sample::factory()->for($project)->create([
            'code' => 'SMP-NEWER',
            'created_at' => now(),
        ]);

        $token = $this->issueTokenForRole('admin');

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/recent-samples');

        $response->assertOk();

        $items = $response->json('data.items');

        $this->assertGreaterThanOrEqual(2, count($items));
        $this->assertSame($newerSample->id, $items[0]['id']);
        $this->assertSame($olderSample->id, $items[1]['id']);
    }

    #[Test]
    public function recent_activity_is_built_from_sample_events(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();
        $actor = User::factory()->create(['name' => 'Sarah Chen']);

        SampleEvent::create([
            'sample_id' => $sample->id,
            'user_id' => $actor->id,
            'event_type' => SampleEventType::STATUS_CHANGED->value,
            'description' => 'Sample status changed from pending to in_progress.',
            'old_status' => 'pending',
            'new_status' => 'in_progress',
            'metadata' => ['source' => 'test'],
            'created_at' => now(),
        ]);

        $token = $this->issueTokenForRole('admin');

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/recent-activity');

        $response
            ->assertOk()
            ->assertJsonPath('data.meta.count', 1)
            ->assertJsonMissingPath('data.meta.total')
            ->assertJsonMissingPath('data.meta.limit')
            ->assertJsonMissingPath('data.meta.has_more')
            ->assertJsonPath('data.items.0.event_type', SampleEventType::STATUS_CHANGED->value)
            ->assertJsonPath('data.items.0.sample_id', $sample->id);
    }

    #[Test]
    public function recent_activity_is_ordered_by_created_at_descending(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create();
        $actor = User::factory()->create();

        SampleEvent::create([
            'sample_id' => $sample->id,
            'user_id' => $actor->id,
            'event_type' => SampleEventType::UPDATED->value,
            'description' => 'Older event',
            'created_at' => now()->subMinute(),
        ]);

        SampleEvent::create([
            'sample_id' => $sample->id,
            'user_id' => $actor->id,
            'event_type' => SampleEventType::CREATED->value,
            'description' => 'Newer event',
            'created_at' => now(),
        ]);

        $token = $this->issueTokenForRole('admin');

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/recent-activity');

        $response->assertOk();

        $items = $response->json('data.items');

        $this->assertGreaterThanOrEqual(2, count($items));
        $this->assertSame('Newer event', $items[0]['description']);
        $this->assertSame('Older event', $items[1]['description']);
    }

    #[Test]
    public function recent_activity_includes_user_name_and_sample_code(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        $sample = Sample::factory()->for($project)->create(['code' => 'SMP-2026-500']);
        $actor = User::factory()->create(['name' => 'Sarah Chen']);

        SampleEvent::create([
            'sample_id' => $sample->id,
            'user_id' => $actor->id,
            'event_type' => SampleEventType::CREATED->value,
            'description' => 'Sample created.',
            'metadata' => ['source' => 'test'],
            'created_at' => now(),
        ]);

        $token = $this->issueTokenForRole('admin');

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/recent-activity');

        $response
            ->assertOk()
            ->assertJsonPath('data.items.0.sample_code', 'SMP-2026-500')
            ->assertJsonPath('data.items.0.user_name', 'Sarah Chen');
    }

    #[Test]
    public function dashboard_endpoints_use_consistent_envelope(): void
    {
        $token = $this->issueTokenForRole('admin');

        $metricsResponse = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/metrics');

        $samplesResponse = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/recent-samples');

        $activityResponse = $this->withBearerToken($token)
            ->getJson('/api/v1/dashboard/recent-activity');

        $metricsResponse->assertOk()->assertJsonStructure(['ok', 'data', 'message']);
        $samplesResponse->assertOk()->assertJsonStructure(['ok', 'data' => ['items', 'meta'], 'message']);
        $activityResponse->assertOk()->assertJsonStructure(['ok', 'data' => ['items', 'meta'], 'message']);

        $samplesResponse
            ->assertJsonStructure(['data' => ['meta' => ['count']]])
            ->assertJsonMissingPath('data.meta.total')
            ->assertJsonMissingPath('data.meta.limit')
            ->assertJsonMissingPath('data.meta.has_more');

        $activityResponse
            ->assertJsonStructure(['data' => ['meta' => ['count']]])
            ->assertJsonMissingPath('data.meta.total')
            ->assertJsonMissingPath('data.meta.limit')
            ->assertJsonMissingPath('data.meta.has_more');
    }
}
