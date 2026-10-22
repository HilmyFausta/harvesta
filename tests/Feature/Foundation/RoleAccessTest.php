<?php

namespace Tests\Feature\Foundation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function sharedRoutes(): array
    {
        return [
            'dashboard' => ['dashboard'],
            'trees' => ['trees.index'],
            'care logs' => ['care-logs.index'],
            'harvests' => ['harvests.index'],
            'categories' => ['categories.index'],
            'tree types' => ['tree-types.index'],
            'blocks' => ['blocks.index'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function adminRoutes(): array
    {
        return [
            'expenses' => ['expenses.index'],
            'sales' => ['sales.index'],
            'reports' => ['reports.index'],
            'users' => ['users.index'],
        ];
    }

    public function test_home_redirects_guests_to_login(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_home_redirects_users_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }

    #[DataProvider('sharedRoutes')]
    #[DataProvider('adminRoutes')]
    public function test_guests_are_redirected_to_login(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    #[DataProvider('sharedRoutes')]
    public function test_staff_can_open_shared_pages(string $route): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route($route))
            ->assertOk();
    }

    #[DataProvider('adminRoutes')]
    public function test_staff_cannot_open_admin_pages(string $route): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route($route))
            ->assertForbidden();
    }

    #[DataProvider('adminRoutes')]
    public function test_admin_can_open_admin_pages(string $route): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route($route))
            ->assertOk();
    }

    public function test_sidebar_hides_admin_menu_from_staff(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('dashboard'))
            ->assertSee(route('trees.index'))
            ->assertDontSee(route('expenses.index'))
            ->assertDontSee(route('users.index'));
    }

    public function test_sidebar_shows_admin_menu_to_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertSee(route('expenses.index'))
            ->assertSee(route('users.index'));
    }
}
