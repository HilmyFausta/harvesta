<?php

namespace Tests\Feature;

use App\Enums\HarvestGrade;
use App\Enums\HarvestUnit;
use App\Enums\TreeStatus;
use App\Enums\UserRole;
use App\Models\Block;
use App\Models\Category;
use App\Models\Harvest;
use App\Models\Sale;
use App\Models\Tree;
use App\Models\TreeType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HarvestTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected Tree $tree;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->staff = User::factory()->create(['role' => UserRole::Staff]);

        $category = Category::factory()->create();
        $treeType = TreeType::factory()->create(['category_id' => $category->id]);
        $block = Block::factory()->create();
        $this->tree = Tree::factory()->create([
            'tree_type_id' => $treeType->id,
            'block_id' => $block->id,
            'status' => TreeStatus::Productive,
        ]);
    }

    public function test_admin_can_view_harvests_list(): void
    {
        Harvest::factory()->count(3)->create(['tree_id' => $this->tree->id]);

        $response = $this->actingAs($this->admin)->get(route('harvests.index'));

        $response->assertOk();
    }

    public function test_staff_can_view_harvests_list(): void
    {
        Harvest::factory()->count(3)->create(['tree_id' => $this->tree->id]);

        $response = $this->actingAs($this->staff)->get(route('harvests.index'));

        $response->assertOk();
    }

    public function test_admin_can_create_harvest(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('harvests.create'));

        $response->assertOk();
    }

    public function test_staff_can_create_harvest(): void
    {
        $response = $this->actingAs($this->staff)
            ->get(route('harvests.create'));

        $response->assertOk();
    }

    public function test_staff_can_edit_own_harvest(): void
    {
        $harvest = Harvest::factory()->create([
            'tree_id' => $this->tree->id,
            'user_id' => $this->staff->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('harvests.edit', $harvest));

        $response->assertOk();
    }

    public function test_staff_cannot_edit_others_harvest(): void
    {
        $harvest = Harvest::factory()->create([
            'tree_id' => $this->tree->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('harvests.edit', $harvest));

        $response->assertForbidden();
    }

    public function test_admin_can_edit_any_harvest(): void
    {
        $harvest = Harvest::factory()->create([
            'tree_id' => $this->tree->id,
            'user_id' => $this->staff->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('harvests.edit', $harvest));

        $response->assertOk();
    }

    public function test_cannot_delete_harvest_with_sales(): void
    {
        $harvest = Harvest::factory()->create([
            'tree_id' => $this->tree->id,
            'user_id' => $this->admin->id,
        ]);

        Sale::factory()->create([
            'harvest_id' => $harvest->id,
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('pages::harvests.index')
            ->call('delete', $harvest->id);

        $this->assertDatabaseHas('harvests', [
            'id' => $harvest->id,
        ]);
    }

    public function test_cannot_add_harvest_to_dead_tree(): void
    {
        $deadTree = Tree::factory()->create([
            'tree_type_id' => $this->tree->tree_type_id,
            'block_id' => $this->tree->block_id,
            'status' => TreeStatus::Dead,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('pages::harvests.create')
            ->set('tree_id', (string) $deadTree->id)
            ->set('harvested_on', now()->format('Y-m-d'))
            ->set('quantity', '10')
            ->set('unit', HarvestUnit::Kg->value)
            ->set('grade', HarvestGrade::Good->value)
            ->call('save')
            ->assertHasErrors('tree_id');

        $this->assertDatabaseMissing('harvests', [
            'tree_id' => $deadTree->id,
        ]);
    }

    public function test_harvested_on_cannot_be_in_future(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::harvests.create')
            ->set('tree_id', (string) $this->tree->id)
            ->set('harvested_on', now()->addDay()->format('Y-m-d'))
            ->set('quantity', '10')
            ->set('unit', HarvestUnit::Kg->value)
            ->set('grade', HarvestGrade::Good->value)
            ->call('save')
            ->assertHasErrors('harvested_on');
    }

    public function test_quantity_must_be_greater_than_zero(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::harvests.create')
            ->set('tree_id', (string) $this->tree->id)
            ->set('harvested_on', now()->format('Y-m-d'))
            ->set('quantity', '0')
            ->set('unit', HarvestUnit::Kg->value)
            ->set('grade', HarvestGrade::Good->value)
            ->call('save')
            ->assertHasErrors('quantity');
    }

    public function test_harvest_can_be_created_successfully(): void
    {
        $this->actingAs($this->staff);

        Livewire::test('pages::harvests.create')
            ->set('tree_id', (string) $this->tree->id)
            ->set('harvested_on', now()->format('Y-m-d'))
            ->set('quantity', '15.5')
            ->set('unit', HarvestUnit::Kg->value)
            ->set('grade', HarvestGrade::Premium->value)
            ->set('notes', 'Test harvest')
            ->call('save')
            ->assertRedirect(route('harvests.index'));

        $this->assertDatabaseHas('harvests', [
            'tree_id' => $this->tree->id,
            'quantity' => '15.50',
            'unit' => HarvestUnit::Kg->value,
            'grade' => HarvestGrade::Premium->value,
            'user_id' => $this->staff->id,
            'notes' => 'Test harvest',
        ]);
    }

    public function test_admin_can_delete_harvest_without_sales(): void
    {
        $harvest = Harvest::factory()->create([
            'tree_id' => $this->tree->id,
            'user_id' => $this->staff->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('pages::harvests.index')
            ->call('delete', $harvest->id);

        $this->assertDatabaseMissing('harvests', [
            'id' => $harvest->id,
        ]);
    }
}
