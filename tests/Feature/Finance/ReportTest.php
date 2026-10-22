<?php

namespace Tests\Feature\Finance;

use App\Enums\ActivityType;
use App\Enums\HarvestUnit;
use App\Models\Block;
use App\Models\CareLog;
use App\Models\Expense;
use App\Models\Harvest;
use App\Models\Sale;
use App\Models\Tree;
use App\Models\TreeType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_open_reports_or_download_exports(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $this->get(route('reports.index'))->assertForbidden();
        $this->get(route('reports.export', [
            'report' => 'harvest',
            'from' => '2026-01-01',
            'to' => '2026-01-31',
        ]))->assertForbidden();
    }

    public function test_admin_can_view_all_reports_for_a_period(): void
    {
        [$block] = $this->reportData();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('reports.index', [
                'from' => '2026-01-01',
                'to' => '2026-01-31',
            ]))
            ->assertOk()
            ->assertSee('Harvest summary')
            ->assertSee('Financial summary')
            ->assertSee('Care activity summary')
            ->assertSee($block->name)
            ->assertSee('Watering')
            ->assertSee('IDR 100,000')
            ->assertSee('IDR 25,000')
            ->assertSee('IDR 75,000')
            ->assertSee('Export CSV');
    }

    public function test_reports_only_include_the_selected_period(): void
    {
        [$block] = $this->reportData();

        $otherBlock = Block::factory()->create(['name' => 'North Block']);
        $otherTree = Tree::factory()->for($otherBlock)->create();

        Harvest::factory()->for($otherTree)->create([
            'harvested_on' => '2025-12-31',
            'quantity' => 99,
            'unit' => HarvestUnit::Kg,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('reports.index', [
                'from' => '2026-01-01',
                'to' => '2026-01-31',
                'group_by' => 'block',
            ]))
            ->assertOk()
            ->assertSee($block->name)
            ->assertDontSee('North Block')
            ->assertDontSee('99.00');
    }

    public function test_harvest_report_can_be_grouped_by_tree_type(): void
    {
        [, $treeType] = $this->reportData();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('reports.index', [
                'from' => '2026-01-01',
                'to' => '2026-01-31',
                'group_by' => 'tree_type',
            ]))
            ->assertOk()
            ->assertSee('Grouped by tree type')
            ->assertSee($treeType->name);
    }

    public function test_harvest_csv_is_streamed_with_english_columns(): void
    {
        [$block] = $this->reportData();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('reports.export', [
                'report' => 'harvest',
                'from' => '2026-01-01',
                'to' => '2026-01-31',
                'group_by' => 'block',
            ]))
            ->assertOk()
            ->assertDownload();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Block,Harvests,"Quantity (kg)","Quantity (pcs)"', $content);
        $this->assertStringContainsString('"'.$block->name.'",1,10.00,0.00', $content);
    }

    public function test_financial_csv_contains_monthly_and_block_rows(): void
    {
        [$block] = $this->reportData();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('reports.export', [
                'report' => 'financial',
                'from' => '2026-01-01',
                'to' => '2026-01-31',
            ]))
            ->assertOk()
            ->assertDownload();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Group,Name,"Sales (IDR)","Expenses (IDR)","Net (IDR)"', $content);
        $this->assertStringContainsString('Month,"Jan 2026",100000.00,25000.00,75000.00', $content);
        $this->assertStringContainsString('Block,"'.$block->name.'",100000.00,25000.00,75000.00', $content);
    }

    public function test_care_csv_contains_activity_totals(): void
    {
        $this->reportData();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('reports.export', [
                'report' => 'care',
                'from' => '2026-01-01',
                'to' => '2026-01-31',
            ]))
            ->assertOk()
            ->assertDownload();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Activity,"Care logs","Last performed"', $content);
        $this->assertStringContainsString('Watering,1,2026-01-15', $content);
    }

    public function test_export_requires_a_valid_period_and_report(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('reports.export', [
                'report' => 'unknown',
                'from' => '2026-02-01',
                'to' => '2026-01-01',
            ]))
            ->assertSessionHasErrors(['report', 'to']);
    }

    /**
     * @return array{0: Block, 1: TreeType, 2: Tree, 3: Harvest}
     */
    private function reportData(): array
    {
        $block = Block::factory()->create(['name' => 'South Block']);
        $treeType = TreeType::factory()->create(['name' => 'Arabica']);
        $tree = Tree::factory()->for($block)->for($treeType, 'treeType')->create();

        $harvest = Harvest::factory()->for($tree)->create([
            'harvested_on' => '2026-01-10',
            'quantity' => 10,
            'unit' => HarvestUnit::Kg,
        ]);

        Sale::factory()->for($harvest)->create([
            'sold_on' => '2026-01-15',
            'quantity' => 2,
            'unit_price' => 50000,
        ]);

        Expense::factory()->for($block)->create([
            'spent_on' => '2026-01-20',
            'amount' => 25000,
        ]);

        CareLog::factory()->for($tree)->create([
            'activity_type' => ActivityType::Watering,
            'performed_on' => '2026-01-15',
        ]);

        return [$block, $treeType, $tree, $harvest];
    }
}
