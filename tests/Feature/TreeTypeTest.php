<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Block;
use App\Models\Category;
use App\Models\Tree;
use App\Models\TreeType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TreeTypeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->staff = User::factory()->create(['role' => UserRole::Staff]);
        $this->category = Category::factory()->create();
    }

    public function test_admin_can_view_tree_types_list(): void
    {
        TreeType::factory()->count(3)->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)->get(route('tree-types.index'));

        $response->assertOk();
    }

    public function test_staff_can_view_tree_types_list(): void
    {
        TreeType::factory()->count(3)->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->staff)->get(route('tree-types.index'));

        $response->assertOk();
    }

    public function test_admin_can_create_tree_type(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('tree-types.create'));

        $response->assertOk();
    }

    public function test_staff_cannot_create_tree_type(): void
    {
        $response = $this->actingAs($this->staff)
            ->get(route('tree-types.create'));

        $response->assertForbidden();
    }

    public function test_admin_can_edit_tree_type(): void
    {
        $treeType = TreeType::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('tree-types.edit', $treeType));

        $response->assertOk();
    }

    public function test_staff_cannot_edit_tree_type(): void
    {
        $treeType = TreeType::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->staff)
            ->get(route('tree-types.edit', $treeType));

        $response->assertForbidden();
    }

    public function test_tree_type_can_be_created_successfully(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::tree-types.create')
            ->set('category_id', (string) $this->category->id)
            ->set('name', 'Durian')
            ->set('scientific_name', 'Durio zibethinus')
            ->set('description', 'King of fruits')
            ->call('save')
            ->assertRedirect(route('tree-types.index'));

        $this->assertDatabaseHas('tree_types', [
            'category_id' => $this->category->id,
            'name' => 'Durian',
            'scientific_name' => 'Durio zibethinus',
        ]);
    }

    public function test_tree_type_name_must_be_unique_per_category(): void
    {
        TreeType::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Mango',
        ]);

        $this->actingAs($this->admin);

        Livewire::test('pages::tree-types.create')
            ->set('category_id', (string) $this->category->id)
            ->set('name', 'Mango')
            ->call('save')
            ->assertHasErrors('name');
    }

    public function test_cannot_delete_tree_type_with_trees(): void
    {
        $treeType = TreeType::factory()->create(['category_id' => $this->category->id]);
        $block = Block::factory()->create();
        Tree::factory()->create([
            'tree_type_id' => $treeType->id,
            'block_id' => $block->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('pages::tree-types.index')
            ->call('delete', $treeType->id);

        $this->assertDatabaseHas('tree_types', [
            'id' => $treeType->id,
        ]);
    }

    public function test_admin_can_delete_tree_type_without_trees(): void
    {
        $treeType = TreeType::factory()->create(['category_id' => $this->category->id]);

        $this->actingAs($this->admin);

        Livewire::test('pages::tree-types.index')
            ->call('delete', $treeType->id);

        $this->assertDatabaseMissing('tree_types', [
            'id' => $treeType->id,
        ]);
    }
}
