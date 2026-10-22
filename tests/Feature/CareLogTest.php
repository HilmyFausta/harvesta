<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\TreeStatus;
use App\Enums\UserRole;
use App\Models\Block;
use App\Models\CareLog;
use App\Models\Category;
use App\Models\Tree;
use App\Models\TreeType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CareLogTest extends TestCase
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

    public function test_admin_can_view_care_logs_list(): void
    {
        CareLog::factory()->count(3)->create(['tree_id' => $this->tree->id]);

        $response = $this->actingAs($this->admin)->get(route('care-logs.index'));

        $response->assertOk();
    }

    public function test_staff_can_view_care_logs_list(): void
    {
        CareLog::factory()->count(3)->create(['tree_id' => $this->tree->id]);

        $response = $this->actingAs($this->staff)->get(route('care-logs.index'));

        $response->assertOk();
    }

    public function test_admin_can_create_care_log(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('care-logs.create'));

        $response->assertOk();
    }

    public function test_staff_can_create_care_log(): void
    {
        $response = $this->actingAs($this->staff)
            ->get(route('care-logs.create'));

        $response->assertOk();
    }

    public function test_staff_can_edit_own_care_log(): void
    {
        $careLog = CareLog::factory()->create([
            'tree_id' => $this->tree->id,
            'user_id' => $this->staff->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('care-logs.edit', $careLog));

        $response->assertOk();
    }

    public function test_staff_cannot_edit_others_care_log(): void
    {
        $careLog = CareLog::factory()->create([
            'tree_id' => $this->tree->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('care-logs.edit', $careLog));

        $response->assertForbidden();
    }

    public function test_admin_can_edit_any_care_log(): void
    {
        $careLog = CareLog::factory()->create([
            'tree_id' => $this->tree->id,
            'user_id' => $this->staff->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('care-logs.edit', $careLog));

        $response->assertOk();
    }

    public function test_cannot_add_care_log_to_dead_tree(): void
    {
        $deadTree = Tree::factory()->create([
            'tree_type_id' => $this->tree->tree_type_id,
            'block_id' => $this->tree->block_id,
            'status' => TreeStatus::Dead,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('pages::care-logs.create')
            ->set('tree_id', (string) $deadTree->id)
            ->set('activity_type', ActivityType::Watering->value)
            ->set('performed_on', now()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors('tree_id');

        $this->assertDatabaseMissing('care_logs', [
            'tree_id' => $deadTree->id,
        ]);
    }

    public function test_performed_on_cannot_be_in_future(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::care-logs.create')
            ->set('tree_id', (string) $this->tree->id)
            ->set('activity_type', ActivityType::Watering->value)
            ->set('performed_on', now()->addDay()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors('performed_on');
    }

    public function test_next_due_on_must_be_after_or_equal_performed_on(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::care-logs.create')
            ->set('tree_id', (string) $this->tree->id)
            ->set('activity_type', ActivityType::Watering->value)
            ->set('performed_on', now()->format('Y-m-d'))
            ->set('next_due_on', now()->subDay()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors('next_due_on');
    }

    public function test_care_log_can_be_created_successfully(): void
    {
        $this->actingAs($this->staff);

        Livewire::test('pages::care-logs.create')
            ->set('tree_id', (string) $this->tree->id)
            ->set('activity_type', ActivityType::Watering->value)
            ->set('performed_on', now()->format('Y-m-d'))
            ->set('notes', 'Test care log')
            ->call('save')
            ->assertRedirect(route('care-logs.index'));

        $this->assertDatabaseHas('care_logs', [
            'tree_id' => $this->tree->id,
            'activity_type' => ActivityType::Watering->value,
            'user_id' => $this->staff->id,
            'notes' => 'Test care log',
        ]);
    }

    public function test_admin_can_delete_care_log(): void
    {
        $careLog = CareLog::factory()->create([
            'tree_id' => $this->tree->id,
            'user_id' => $this->staff->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('pages::care-logs.index')
            ->call('delete', $careLog->id);

        $this->assertDatabaseMissing('care_logs', [
            'id' => $careLog->id,
        ]);
    }
}
