<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(string $roleName = 'analyst'): array
    {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);

        $user = User::factory()->create();
        $user->assignRole($roleName);

        $token = auth('api')->login($user);

        return [$user, $token];
    }

    public function test_get_profile_returns_user_data(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withBearerToken($token)->getJson('/api/v1/settings/profile');

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonStructure([
                'ok',
                'data' => ['id', 'name', 'email', 'roles'],
                'message',
            ]);
    }

    public function test_update_profile_changes_name_and_email(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withBearerToken($token)->patchJson('/api/v1/settings/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.email', 'updated@example.com');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }

    public function test_update_profile_rejects_duplicate_email_from_another_user(): void
    {
        [$user, $token] = $this->createUserWithToken();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->withBearerToken($token)->patchJson('/api/v1/settings/profile', [
            'name' => 'Someone',
            'email' => 'taken@example.com',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_update_profile_allows_keeping_own_email(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withBearerToken($token)->patchJson('/api/v1/settings/profile', [
            'name' => 'Same User',
            'email' => $user->email,
        ]);

        $response->assertOk()->assertJsonPath('ok', true);
    }

    public function test_get_preferences_returns_defaults_when_no_row_exists(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $this->assertDatabaseMissing('user_preferences', ['user_id' => $user->id]);

        $response = $this->withBearerToken($token)->getJson('/api/v1/settings/preferences');

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'notify_urgent_sample_alerts',
                    'notify_sample_completion',
                    'notify_daily_activity_digest',
                    'notify_project_updates',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('user_preferences', ['user_id' => $user->id]);
    }

    public function test_update_preferences_persists_boolean_values(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withBearerToken($token)->patchJson('/api/v1/settings/preferences', [
            'notify_urgent_sample_alerts' => true,
            'notify_sample_completion' => false,
            'notify_daily_activity_digest' => true,
            'notify_project_updates' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.notify_urgent_sample_alerts', true)
            ->assertJsonPath('data.notify_sample_completion', false);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'notify_urgent_sample_alerts' => true,
            'notify_sample_completion' => false,
        ]);
    }

    public function test_change_password_succeeds_with_correct_current_password(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withBearerToken($token)->postJson('/api/v1/settings/change-password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()->assertJsonPath('ok', true);
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withBearerToken($token)->postJson('/api/v1/settings/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'INVALID_PASSWORD');
    }

    public function test_change_password_validates_confirmation(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withBearerToken($token)->postJson('/api/v1/settings/change-password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_token_remains_valid_after_password_change(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $this->withBearerToken($token)->postJson('/api/v1/settings/change-password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertOk();

        $this->withBearerToken($token)->getJson('/api/v1/settings/profile')
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_settings_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/settings/profile')->assertUnauthorized();
        $this->patchJson('/api/v1/settings/profile', [])->assertUnauthorized();
        $this->getJson('/api/v1/settings/preferences')->assertUnauthorized();
        $this->patchJson('/api/v1/settings/preferences', [])->assertUnauthorized();
        $this->postJson('/api/v1/settings/change-password', [])->assertUnauthorized();
    }
}
