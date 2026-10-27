<?php

namespace Tests\Feature\Foundation;

use App\Enums\TreeStatus;
use App\Enums\UserRole;
use App\Models\Block;
use App\Models\CareLog;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Harvest;
use App\Models\Sale;
use App\Models\Tree;
use App\Models\TreeType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('photos');
    }

    public function test_seeder_creates_the_expected_dataset(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::firstWhere('email', 'admin@example.com');

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertSame(UserRole::Staff, User::firstWhere('email', 'staff@example.com')->role);

        $this->assertSame(4, Category::count());
        $this->assertSame(11, TreeType::count());
        $this->assertSame(3, Block::count());
        $this->assertSame(40, Tree::count());
        $this->assertSame(100, CareLog::count());
        $this->assertSame(40, Harvest::count());
        $this->assertSame(30, Expense::count());
        $this->assertSame(20, Sale::count());
    }

    public function test_seeded_data_follows_the_business_rules(): void
    {
        $this->seed(DatabaseSeeder::class);

        $deadTreeIds = Tree::where('status', TreeStatus::Dead)->pluck('id');
        $this->assertNotEmpty($deadTreeIds);
        $this->assertSame(0, CareLog::whereIn('tree_id', $deadTreeIds)->count());
        $this->assertSame(0, Harvest::whereIn('tree_id', $deadTreeIds)->count());

        Harvest::withSum('sales', 'quantity')->get()->each(
            fn (Harvest $harvest) => $this->assertGreaterThanOrEqual(0, $harvest->remainingQuantity()),
        );

        $this->assertSame(0, CareLog::whereColumn('next_due_on', '<', 'performed_on')->count());
        $this->assertSame(0, Sale::where('total_amount', '!=', DB::raw('round(quantity * unit_price, 2)'))->count());
        $this->assertSame(0, Tree::where('planted_on', '>', today())->count());
        $this->assertSame(0, Harvest::where('harvested_on', '>', today())->count());
        $this->assertTrue(CareLog::where('next_due_on', '<=', today()->addDays(7))->exists());
    }

    public function test_living_trees_get_a_sample_photo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $withPhoto = Tree::whereNotNull('photo_path')->get();

        $this->assertCount(32, $withPhoto);
        $withPhoto->each(fn (Tree $tree) => Storage::disk('photos')->assertExists($tree->photo_path));
        $this->assertSame(0, Tree::whereIn('status', [TreeStatus::Seedling, TreeStatus::Dead])->whereNotNull('photo_path')->count());
    }

    public function test_harvests_cover_the_last_twelve_months(): void
    {
        $this->seed(DatabaseSeeder::class);

        $months = Harvest::pluck('harvested_on')->map->format('Y-m')->unique();

        $this->assertCount(12, $months);
    }

    public function test_seeding_twice_does_not_duplicate_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::count());
        $this->assertSame(4, Category::count());
        $this->assertSame(40, Tree::count());
    }
}
