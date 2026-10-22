<?php

namespace Tests\Feature\Foundation;

use App\Models\CareLog;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Harvest;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_but_not_change_master_data(): void
    {
        $staff = User::factory()->staff()->create();
        $category = Category::factory()->create();

        $this->assertTrue($staff->can('viewAny', Category::class));
        $this->assertFalse($staff->can('create', Category::class));
        $this->assertFalse($staff->can('update', $category));
        $this->assertFalse($staff->can('delete', $category));
    }

    public function test_staff_can_edit_trees_but_not_delete_them(): void
    {
        $staff = User::factory()->staff()->create();
        $tree = Tree::factory()->create();

        $this->assertTrue($staff->can('create', Tree::class));
        $this->assertTrue($staff->can('update', $tree));
        $this->assertFalse($staff->can('delete', $tree));
        $this->assertTrue(User::factory()->admin()->create()->can('delete', $tree));
    }

    public function test_staff_can_only_update_their_own_field_records(): void
    {
        $staff = User::factory()->staff()->create();
        $own = CareLog::factory()->for($staff)->create();
        $others = CareLog::factory()->create();
        $harvest = Harvest::factory()->for($staff)->create();

        $this->assertTrue($staff->can('update', $own));
        $this->assertFalse($staff->can('update', $others));
        $this->assertFalse($staff->can('delete', $own));
        $this->assertTrue($staff->can('update', $harvest));
        $this->assertFalse($staff->can('delete', $harvest));
    }

    public function test_finance_is_admin_only(): void
    {
        $this->assertFalse(User::factory()->staff()->create()->can('viewAny', Expense::class));
        $this->assertTrue(User::factory()->admin()->create()->can('viewAny', Expense::class));
    }

    public function test_the_last_admin_cannot_be_deleted_or_demoted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->isLastAdmin());
        $this->assertFalse($admin->can('delete', $admin));
        $this->assertFalse($admin->can('changeRole', $admin));

        $other = User::factory()->admin()->create();

        $this->assertFalse($admin->fresh()->isLastAdmin());
        $this->assertTrue($admin->can('delete', $other));
    }
}
