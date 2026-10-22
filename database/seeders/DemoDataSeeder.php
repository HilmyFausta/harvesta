<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\ExpenseCategory;
use App\Enums\HarvestGrade;
use App\Enums\HarvestUnit;
use App\Enums\TreeStatus;
use App\Enums\UserRole;
use App\Models\Block;
use App\Models\CareLog;
use App\Models\Expense;
use App\Models\Harvest;
use App\Models\Sale;
use App\Models\Tree;
use App\Models\TreeType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Faker\Generator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Unit and price range (IDR per unit) for each harvestable tree type.
     *
     * @var array<string, array{0: HarvestUnit, 1: int, 2: int}>
     */
    protected const HARVESTABLE = [
        'Durian' => [HarvestUnit::Pcs, 45000, 90000],
        'Mango' => [HarvestUnit::Kg, 15000, 25000],
        'Rambutan' => [HarvestUnit::Kg, 10000, 18000],
        'Avocado' => [HarvestUnit::Kg, 20000, 35000],
        'Clove' => [HarvestUnit::Kg, 90000, 130000],
        'Nutmeg' => [HarvestUnit::Kg, 70000, 110000],
    ];

    /**
     * Expense amount range (IDR) and a sample description per category.
     *
     * @var array<string, array{0: int, 1: int, 2: string}>
     */
    protected const EXPENSES = [
        'fertilizer' => [300000, 2000000, 'NPK and organic compost'],
        'pesticide' => [150000, 1000000, 'Insecticide spray'],
        'labor' => [1000000, 3500000, 'Weekly wages for field workers'],
        'equipment' => [200000, 5000000, 'Pruning shears and ladders'],
        'water' => [100000, 600000, 'Irrigation pump fuel and maintenance'],
        'transport' => [100000, 800000, 'Delivery to the wholesale market'],
        'other' => [50000, 500000, 'Miscellaneous supplies'],
    ];

    protected const BUYERS = [
        'Fresh Harvest Market', 'Green Valley Grocers', 'Sunrise Fruit Stall',
        'Nusantara Spice Traders', 'City Juice Bar', 'Local Cooperative',
    ];

    protected Generator $faker;

    protected CarbonImmutable $today;

    /** @var list<int> */
    protected array $userIds;

    /**
     * Seed twelve months of sample orchard data.
     */
    public function run(): void
    {
        if (Tree::withTrashed()->exists()) {
            $this->command?->warn('Demo data skipped: trees already exist.');

            return;
        }

        // A fixed seed keeps the numbers identical on every machine.
        $this->faker = fake();
        $this->faker->seed(20261028);
        $this->today = CarbonImmutable::today();
        $this->userIds = array_values(
            User::whereIn('role', [UserRole::Admin, UserRole::Staff])->pluck('id')->map(intval(...))->all(),
        );

        $trees = $this->seedTrees();
        $this->seedCareLogs($trees);
        $harvests = $this->seedHarvests($trees);
        $this->seedSales($harvests);
        $this->seedExpenses();
    }

    /**
     * @return Collection<int, Tree>
     */
    protected function seedTrees(): Collection
    {
        $types = TreeType::with('category')->get()->groupBy('category.name');
        $blocks = Block::pluck('id', 'name');

        // [category, status, how many]
        $plan = [
            ['Fruit', TreeStatus::Productive, 12], ['Fruit', TreeStatus::Growing, 2],
            ['Fruit', TreeStatus::NeedsAttention, 2], ['Fruit', TreeStatus::Seedling, 1],
            ['Fruit', TreeStatus::Dead, 1],
            ['Spice', TreeStatus::Productive, 4], ['Spice', TreeStatus::Growing, 1],
            ['Spice', TreeStatus::NeedsAttention, 1],
            ['Timber', TreeStatus::Growing, 6], ['Timber', TreeStatus::Seedling, 3],
            ['Timber', TreeStatus::Dead, 1],
            ['Ornamental', TreeStatus::Growing, 3], ['Ornamental', TreeStatus::Seedling, 2],
            ['Ornamental', TreeStatus::NeedsAttention, 1],
        ];

        $trees = new Collection;

        foreach ($plan as [$category, $status, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $block = match (true) {
                    $status === TreeStatus::Seedling => 'River Block',
                    $category === 'Fruit' => 'North Block',
                    default => 'East Block',
                };

                $trees->push(Tree::forceCreate([
                    'tree_type_id' => $this->faker->randomElement($types[$category])->id,
                    'block_id' => $blocks[$block],
                    'planted_on' => $this->plantedOn($status),
                    'status' => $status,
                    'height_cm' => $this->height($status),
                    'notes' => $status === TreeStatus::NeedsAttention
                        ? 'Leaves are turning yellow; check for root rot.'
                        : null,
                    'created_by' => $this->faker->randomElement($this->userIds),
                ]));
            }
        }

        return $trees->load('treeType');
    }

    /**
     * @param  Collection<int, Tree>  $trees
     */
    protected function seedCareLogs(Collection $trees): void
    {
        $living = $trees->filter->acceptsRecords()->values();

        // 88 past activities without a follow-up date.
        for ($i = 0; $i < 88; $i++) {
            $this->createCareLog(
                $this->faker->randomElement($living),
                $this->today->subDays($this->faker->numberBetween(31, 360)),
                null,
            );
        }

        // 12 recent activities that feed the "Upcoming Care" widget:
        // overdue, due within a week, and due later.
        foreach ([-4, -2, -1, 0, 1, 2, 3, 5, 7, 12, 18, 25] as $dueInDays) {
            $this->createCareLog(
                $this->faker->randomElement($living),
                $this->today->subDays($this->faker->numberBetween(3, 25)),
                $this->today->addDays($dueInDays),
            );
        }
    }

    protected function createCareLog(Tree $tree, CarbonImmutable $performedOn, ?CarbonImmutable $nextDueOn): void
    {
        $activity = $this->faker->randomElement(ActivityType::cases());

        CareLog::forceCreate([
            'tree_id' => $tree->id,
            'activity_type' => $activity,
            'performed_on' => $performedOn,
            'next_due_on' => $nextDueOn !== null && $nextDueOn->lt($performedOn) ? $performedOn : $nextDueOn,
            'notes' => $this->faker->boolean(40) ? $activity->label().' completed without issues.' : null,
            'user_id' => $this->faker->randomElement($this->userIds),
        ]);
    }

    /**
     * @param  Collection<int, Tree>  $trees
     * @return Collection<int, Harvest>
     */
    protected function seedHarvests(Collection $trees): Collection
    {
        $productive = $trees
            ->filter(fn (Tree $tree) => $tree->status === TreeStatus::Productive
                && isset(self::HARVESTABLE[$tree->treeType->name]))
            ->values();

        $harvests = new Collection;

        for ($i = 0; $i < 40; $i++) {
            $tree = $productive[$i % $productive->count()];
            $unit = self::HARVESTABLE[$tree->treeType->name][0];

            $harvests->push(Harvest::forceCreate([
                'tree_id' => $tree->id,
                'harvested_on' => $this->dayInMonth($i % 12),
                'quantity' => $unit === HarvestUnit::Pcs
                    ? $this->faker->numberBetween(10, 60)
                    : $this->faker->randomFloat(2, 20, 150),
                'unit' => $unit,
                'grade' => $this->faker->randomElement([
                    HarvestGrade::Premium, HarvestGrade::Good, HarvestGrade::Good,
                    HarvestGrade::Good, HarvestGrade::Fair, HarvestGrade::Poor,
                ]),
                'notes' => null,
                'user_id' => $this->faker->randomElement($this->userIds),
            ]));
        }

        return $harvests->each(
            fn (Harvest $harvest) => $harvest->setRelation('tree', $trees->firstWhere('id', $harvest->tree_id)),
        );
    }

    /**
     * Each sale sells part of a different harvest, so no harvest is oversold.
     *
     * @param  Collection<int, Harvest>  $harvests
     */
    protected function seedSales(Collection $harvests): void
    {
        foreach ($this->faker->randomElements($harvests->all(), 20) as $harvest) {
            [$unit, $minPrice, $maxPrice] = self::HARVESTABLE[$harvest->tree->treeType->name];

            $quantity = (float) $harvest->quantity * $this->faker->randomFloat(2, 0.3, 0.9);
            $soldOn = $harvest->harvested_on->addDays($this->faker->numberBetween(0, 5));

            Sale::forceCreate([
                'harvest_id' => $harvest->id,
                'buyer_name' => $this->faker->randomElement(self::BUYERS),
                'sold_on' => $soldOn->gt($this->today) ? $this->today : $soldOn,
                'quantity' => $unit === HarvestUnit::Pcs ? floor($quantity) : floor($quantity * 100) / 100,
                'unit_price' => round($this->faker->numberBetween($minPrice, $maxPrice), -2),
                'notes' => null,
                'user_id' => $this->faker->randomElement($this->userIds),
            ]);
        }
    }

    protected function seedExpenses(): void
    {
        $blockIds = Block::pluck('id')->all();

        for ($i = 0; $i < 30; $i++) {
            $category = $this->faker->randomElement(ExpenseCategory::cases());
            [$min, $max, $description] = self::EXPENSES[$category->value];

            Expense::forceCreate([
                'block_id' => $this->faker->boolean(70) ? $this->faker->randomElement($blockIds) : null,
                'category' => $category,
                'amount' => round($this->faker->numberBetween($min, $max), -3),
                'spent_on' => $this->dayInMonth($i % 12),
                'description' => $description,
                'user_id' => $this->faker->randomElement($this->userIds),
            ]);
        }
    }

    /**
     * A random day in the month that is $monthsAgo months before today,
     * never later than today.
     */
    protected function dayInMonth(int $monthsAgo): CarbonImmutable
    {
        $month = $this->today->startOfMonth()->subMonths($monthsAgo);
        $day = $month->addDays($this->faker->numberBetween(0, $month->daysInMonth - 1));

        return $day->gt($this->today) ? $this->today : $day;
    }

    protected function plantedOn(TreeStatus $status): CarbonImmutable
    {
        [$minDays, $maxDays] = match ($status) {
            TreeStatus::Seedling => [30, 360],
            TreeStatus::Growing => [730, 1825],
            TreeStatus::Productive => [2190, 4380],
            TreeStatus::NeedsAttention, TreeStatus::Dead => [1095, 3650],
        };

        return $this->today->subDays($this->faker->numberBetween($minDays, $maxDays));
    }

    protected function height(TreeStatus $status): float
    {
        return match ($status) {
            TreeStatus::Seedling => $this->faker->randomFloat(2, 30, 120),
            TreeStatus::Growing => $this->faker->randomFloat(2, 150, 450),
            default => $this->faker->randomFloat(2, 400, 1500),
        };
    }
}
