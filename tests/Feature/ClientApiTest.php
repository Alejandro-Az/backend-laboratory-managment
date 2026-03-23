<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientApiTest extends TestCase
{
    use RefreshDatabase;

    private function issueTokenForRole(string $roleName): string
    {
        $permissions = [
            'clients.view',
            'clients.create',
            'clients.update',
            'clients.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $analystRole = Role::firstOrCreate(['name' => 'analyst', 'guard_name' => 'api']);

        $adminRole->syncPermissions($permissions);
        $analystRole->syncPermissions(['clients.view']);

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return auth('api')->login($user);
    }

    public function test_admin_can_create_client(): void
    {
        $token = $this->issueTokenForRole('admin');

        $response = $this->postJson('/api/v1/clients', [
            'name' => 'Acme Labs',
            'contact_email' => 'contact@acme.test',
            'contact_phone' => '123456789',
            'location' => 'Madrid',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertCreated()->assertJson([
            'ok' => true,
            'message' => 'Client created successfully.',
        ]);

        $this->assertDatabaseHas('clients', ['name' => 'Acme Labs']);
    }

    public function test_analyst_cannot_create_client(): void
    {
        $token = $this->issueTokenForRole('analyst');

        $response = $this->postJson('/api/v1/clients', [
            'name' => 'Forbidden Labs',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_client_validation_uses_error_envelope(): void
    {
        $token = $this->issueTokenForRole('admin');

        $response = $this->postJson('/api/v1/clients', [
            'contact_email' => 'invalid-email',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_analyst_can_list_clients(): void
    {
        Client::factory()->count(2)->create();

        $token = $this->issueTokenForRole('analyst');

        $response = $this->getJson('/api/v1/clients', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => ['items', 'meta'],
                'message',
            ]);
    }
}
