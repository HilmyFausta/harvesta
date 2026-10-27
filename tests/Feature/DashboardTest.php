<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\HarvestUnit;
use App\Enums\TreeStatus;
use App\Models\CareLog;
use App\Models\Expense;
use App\Models\Harvest;
use App\Models\Sale;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_shows_farm_overview_and_upcoming_care(): void
    {
        $tree = Tree::factory()->status(TreeStatus::Productive)->create();
        Harvest::factory()->for($tree)->create([
            'harvested_on' => today(),
            'quantity' => 12,
            'unit' => HarvestUnit::Kg,
        ]);

        CareLog::factory()->for($tree)->create([
            'activity_type' => ActivityType::Watering,
            'next_due_on' => today()->addDays(2),
        ]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total trees')
            ->assertSee('Harvest this month')
            ->assertSee('12 kg')
            ->assertSee('Upcoming care')
            ->assertSee($tree->code)
            ->assertSee('Watering')
            ->assertSee('Recent harvests')
            ->assertDontSee('Sales this month')
            ->assertDontSee('Sales and expenses');
    }

    public function test_upcoming_care_shows_whole_time_units_and_correct_status(): void
    {
        $this->travelTo(today()->setTime(15, 30));

        $tree = Tree::factory()->create();

        foreach ([-1, 0, 1, 3] as $days) {
            CareLog::factory()->for($tree)->create(['next_due_on' => today()->addDays($days)]);
        }

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Overdue')
            ->assertSee('Due today')
            ->assertSee('In 8 hours')
            ->assertSee('In 2 days')
            ->assertSee('favicons/dashboard.svg');
    }

    public function test_admin_sees_financial_summary_and_report_link(): void
    {
        $harvest = Harvest::factory()->create([
            'harvested_on' => today(),
            'quantity' => 100,
        ]);

        Sale::factory()->for($harvest)->create([
            'sold_on' => today(),
            'quantity' => 2,
            'unit_price' => 10000,
        ]);

        Expense::factory()->create([
            'spent_on' => today(),
            'amount' => 5000,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('View reports')
            ->assertSee('Sales this month')
            ->assertSee('IDR 20,000')
            ->assertSee('Expenses this month')
            ->assertSee('IDR 5,000')
            ->assertSee('Net this month')
            ->assertSee('IDR 15,000')
            ->assertSee('Sales and expenses');
    }

    public function test_dashboard_stays_within_the_query_budget(): void
    {
        $this->createDashboardData();

        $this->actingAs(User::factory()->admin()->create());

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(route('dashboard'))->assertOk();

        $this->assertLessThanOrEqual(15, count(DB::getQueryLog()));
    }

    public function test_dashboard_renders_for_an_empty_database(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No upcoming care activities.')
            ->assertSee('No harvests recorded yet.')
            ->assertSee('IDR 0');
    }

    private function createDashboardData(): void
    {
        $tree = Tree::factory()->create();
        $harvest = Harvest::factory()->for($tree)->create([
            'harvested_on' => today(),
        ]);

        Sale::factory()->for($harvest)->create(['sold_on' => today()]);
        Expense::factory()->create(['spent_on' => today()]);

        CareLog::factory()->for($tree)->create([
            'next_due_on' => today()->addDay(),
        ]);
    }
}
