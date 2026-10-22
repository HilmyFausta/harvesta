<?php

namespace Tests\Feature\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_staff_cannot_open_user_management_pages(): void
    {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->staff()->create());

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.edit', $target))->assertForbidden();
    }

    public function test_admin_can_view_users(): void
    {
        $user = User::factory()->create(['name' => 'Field Worker']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Field Worker')
            ->assertSee($user->email);
    }

    public function test_admin_can_create_a_user(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::users.create')
            ->set('name', 'Field Worker')
            ->set('email', 'FIELD@example.com')
            ->set('role', UserRole::Staff->value)
            ->set('password', 'password123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'field@example.com')->firstOrFail();

        $this->assertSame('Field Worker', $user->name);
        $this->assertSame(UserRole::Staff, $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_admin_can_create_another_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::users.create')
            ->set('name', 'Second Admin')
            ->set('email', 'second-admin@example.com')
            ->set('role', UserRole::Admin->value)
            ->set('password', 'password123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(UserRole::Admin, User::where('email', 'second-admin@example.com')->firstOrFail()->role);
    }

    public function test_email_must_be_unique(): void
    {
        $existing = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::users.create')
            ->set('name', 'Duplicate Email')
            ->set('email', $existing->email)
            ->set('role', UserRole::Staff->value)
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_admin_can_update_a_user_without_changing_the_password(): void
    {
        $user = User::factory()->staff()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::users.edit', ['user' => $user])
            ->set('name', 'Updated Worker')
            ->set('email', 'updated@example.com')
            ->set('role', UserRole::Admin->value)
            ->set('password', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertSame('Updated Worker', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_admin_can_change_a_user_password(): void
    {
        $user = User::factory()->staff()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::users.edit', ['user' => $user])
            ->set('password', 'new-password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_the_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test('pages::users.edit', ['user' => $admin])
            ->set('role', UserRole::Staff->value)
            ->call('save')
            ->assertStatus(403);

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_the_last_admin_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test('pages::users.index')
            ->call('delete', $admin->id)
            ->assertStatus(403);

        $this->assertNotNull($admin->fresh());
    }

    public function test_admin_can_delete_another_user(): void
    {
        $user = User::factory()->staff()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::users.index')
            ->call('delete', $user->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_search_matches_name_or_email(): void
    {
        User::factory()->create(['name' => 'Alpha Worker', 'email' => 'alpha@example.com']);
        User::factory()->create(['name' => 'Beta Worker', 'email' => 'beta@example.com']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::users.index')
            ->set('search', 'Alpha')
            ->assertSee('Alpha Worker')
            ->assertDontSee('Beta Worker');
    }

    public function test_role_filter_narrows_the_list(): void
    {
        User::factory()->admin()->create(['name' => 'Admin Person']);
        User::factory()->staff()->create(['name' => 'Staff Person']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::users.index')
            ->set('role', UserRole::Staff->value)
            ->assertSee('Staff Person')
            ->assertDontSee('Admin Person');
    }
}
