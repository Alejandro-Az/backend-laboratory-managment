<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    private function issueTokenForRole(string $roleName): string
    {
        $permissions = [
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $analystRole = Role::firstOrCreate(['name' => 'analyst', 'guard_name' => 'api']);

        $adminRole->syncPermissions($permissions);
        $analystRole->syncPermissions(['projects.view']);

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return auth('api')->login($user);
    }

    public function test_admin_can_create_project(): void
    {
        $client = Client::factory()->create();
        $token = $this->issueTokenForRole('admin');

        $response = $this->postJson('/api/v1/projects', [
            'client_id' => $client->id,
            'name' => 'Project Apollo',
            'status' => ProjectStatus::ACTIVE->value,
            'started_at' => '2026-03-17',
            'ended_at' => null,
            'description' => 'Initial phase',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'Project created successfully.');

        $this->assertDatabaseHas('projects', ['name' => 'Project Apollo']);
    }

    public function test_analyst_cannot_create_project(): void
    {
        $client = Client::factory()->create();
        $token = $this->issueTokenForRole('analyst');

        $response = $this->postJson('/api/v1/projects', [
            'client_id' => $client->id,
            'name' => 'Forbidden Project',
            'status' => ProjectStatus::ACTIVE->value,
            'started_at' => '2026-03-17',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_projects_list_supports_status_filter(): void
    {
        $client = Client::factory()->create();
        Project::factory()->create(['client_id' => $client->id, 'status' => ProjectStatus::ACTIVE->value]);
        Project::factory()->create(['client_id' => $client->id, 'status' => ProjectStatus::ON_HOLD->value]);

        $token = $this->issueTokenForRole('analyst');

        $response = $this->getJson('/api/v1/projects?status='.ProjectStatus::ACTIVE->value, [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_project_validation_uses_error_envelope(): void
    {
        $token = $this->issueTokenForRole('admin');

        $response = $this->postJson('/api/v1/projects', [
            'client_id' => 999999,
            'name' => 'Broken project',
            'status' => 'bad_status',
            'started_at' => 'invalid-date',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }
}
