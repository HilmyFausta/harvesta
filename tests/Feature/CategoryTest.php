<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\TreeType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->staff = User::factory()->create(['role' => UserRole::Staff]);
    }

    public function test_admin_can_view_categories_list(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('categories.index'));

        $response->assertOk();
    }

    public function test_staff_can_view_categories_list(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->staff)->get(route('categories.index'));

        $response->assertOk();
    }

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('categories.create'));

        $response->assertOk();
    }

    public function test_staff_cannot_create_category(): void
    {
        $response = $this->actingAs($this->staff)
            ->get(route('categories.create'));

        $response->assertForbidden();
    }

    public function test_admin_can_edit_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('categories.edit', $category));

        $response->assertOk();
    }

    public function test_staff_cannot_edit_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->staff)
            ->get(route('categories.edit', $category));

        $response->assertForbidden();
    }

    public function test_category_can_be_created_successfully(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::categories.create')
            ->set('name', 'Test Category')
            ->set('description', 'Test description')
            ->call('save')
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'description' => 'Test description',
        ]);
    }

    public function test_category_name_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Fruit']);

        $this->actingAs($this->admin);

        Livewire::test('pages::categories.create')
            ->set('name', 'Fruit')
            ->call('save')
            ->assertHasErrors('name');
    }

    public function test_cannot_delete_category_with_tree_types(): void
    {
        $category = Category::factory()->create();
        TreeType::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->admin);

        Livewire::test('pages::categories.index')
            ->call('delete', $category->id);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_admin_can_delete_category_without_tree_types(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test('pages::categories.index')
            ->call('delete', $category->id);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }
}
