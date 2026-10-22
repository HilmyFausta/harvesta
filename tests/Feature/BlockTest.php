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

class BlockTest extends TestCase
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

    public function test_admin_can_view_blocks_list(): void
    {
        Block::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('blocks.index'));

        $response->assertOk();
    }

    public function test_staff_can_view_blocks_list(): void
    {
        Block::factory()->count(3)->create();

        $response = $this->actingAs($this->staff)->get(route('blocks.index'));

        $response->assertOk();
    }

    public function test_admin_can_view_block_detail(): void
    {
        $block = Block::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('blocks.show', $block));

        $response->assertOk();
    }

    public function test_staff_can_view_block_detail(): void
    {
        $block = Block::factory()->create();

        $response = $this->actingAs($this->staff)->get(route('blocks.show', $block));

        $response->assertOk();
    }

    public function test_admin_can_create_block(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('blocks.create'));

        $response->assertOk();
    }

    public function test_staff_cannot_create_block(): void
    {
        $response = $this->actingAs($this->staff)
            ->get(route('blocks.create'));

        $response->assertForbidden();
    }

    public function test_admin_can_edit_block(): void
    {
        $block = Block::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('blocks.edit', $block));

        $response->assertOk();
    }

    public function test_staff_cannot_edit_block(): void
    {
        $block = Block::factory()->create();

        $response = $this->actingAs($this->staff)
            ->get(route('blocks.edit', $block));

        $response->assertForbidden();
    }

    public function test_block_can_be_created_successfully(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::blocks.create')
            ->set('name', 'North Field')
            ->set('area_sqm', '1000.50')
            ->set('location_note', 'Near the main gate')
            ->set('description', 'Main production area')
            ->call('save')
            ->assertRedirect(route('blocks.index'));

        $this->assertDatabaseHas('blocks', [
            'name' => 'North Field',
            'area_sqm' => '1000.50',
            'location_note' => 'Near the main gate',
        ]);
    }

    public function test_block_name_must_be_unique(): void
    {
        Block::factory()->create(['name' => 'Block A']);

        $this->actingAs($this->admin);

        Livewire::test('pages::blocks.create')
            ->set('name', 'Block A')
            ->call('save')
            ->assertHasErrors('name');
    }

    public function test_block_area_must_be_non_negative(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::blocks.create')
            ->set('name', 'Test Block')
            ->set('area_sqm', '-10')
            ->call('save')
            ->assertHasErrors('area_sqm');
    }

    public function test_cannot_delete_block_with_trees(): void
    {
        $block = Block::factory()->create();
        $category = Category::factory()->create();
        $treeType = TreeType::factory()->create(['category_id' => $category->id]);
        Tree::factory()->create([
            'tree_type_id' => $treeType->id,
            'block_id' => $block->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('pages::blocks.index')
            ->call('delete', $block->id);

        $this->assertDatabaseHas('blocks', [
            'id' => $block->id,
        ]);
    }

    public function test_admin_can_delete_block_without_trees(): void
    {
        $block = Block::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test('pages::blocks.index')
            ->call('delete', $block->id);

        $this->assertDatabaseMissing('blocks', [
            'id' => $block->id,
        ]);
    }

    public function test_block_detail_shows_correct_summary(): void
    {
        $block = Block::factory()->create();
        $category = Category::factory()->create();
        $treeType = TreeType::factory()->create(['category_id' => $category->id]);
        Tree::factory()->count(5)->create([
            'tree_type_id' => $treeType->id,
            'block_id' => $block->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('pages::blocks.show', ['block' => $block])
            ->assertSee($block->name)
            ->assertSee('5');
    }
}
