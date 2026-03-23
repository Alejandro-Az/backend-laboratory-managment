<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_user_resource_with_roles_and_permissions(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'clients.view',
            'guard_name' => 'api',
        ]);

        $role = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api',
        ]);

        $role->syncPermissions([$permission]);

        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $user->assignRole('admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.user.email', 'admin@example.com')
            ->assertJsonPath('data.user.roles.0', 'admin')
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'token',
                    'token_type',
                    'expires_in',
                    'user' => ['id', 'name', 'email', 'roles', 'permissions'],
                ],
                'message',
            ]);
    }

    public function test_me_returns_user_resource_for_authenticated_user(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'projects.view',
            'guard_name' => 'api',
        ]);

        $role = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api',
        ]);

        $role->syncPermissions([$permission]);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $token = auth('api')->login($user);

        $response = $this->getJson('/api/v1/me', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.roles.0', 'admin')
            ->assertJsonStructure([
                'ok',
                'data' => ['id', 'name', 'email', 'roles', 'permissions'],
                'message',
            ]);
    }
}
