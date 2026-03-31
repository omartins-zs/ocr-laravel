<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDocumentFixtures;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use CreatesDocumentFixtures;
    use RefreshDatabase;

    public function test_admin_can_access_users_screen(): void
    {
        $admin = $this->createUser(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Gestao de usuarios');
    }

    public function test_viewer_cannot_access_users_screen(): void
    {
        $viewer = $this->createUser(['role' => UserRole::Viewer->value]);

        $response = $this->actingAs($viewer)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_manager_can_update_user_role_and_status(): void
    {
        $manager = $this->createUser(['role' => UserRole::Manager->value]);
        $target = $this->createUser([
            'role' => UserRole::Viewer->value,
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->patch(route('users.update', $target), [
            'role' => UserRole::Reviewer->value,
            'is_active' => '0',
        ]);

        $response->assertRedirect();

        $target->refresh();
        $this->assertSame(UserRole::Reviewer, $target->role);
        $this->assertFalse($target->is_active);
    }
}
