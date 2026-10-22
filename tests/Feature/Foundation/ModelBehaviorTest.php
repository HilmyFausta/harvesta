<?php

namespace Tests\Feature\Foundation;

use App\Models\CareLog;
use App\Models\Harvest;
use App\Models\Sale;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_tree_code_is_generated_when_blank(): void
    {
        $first = Tree::factory()->create(['code' => null]);
        $second = Tree::factory()->create(['code' => '']);

        $this->assertSame('T-0001', $first->code);
        $this->assertSame('T-0002', $second->code);
    }

    public function test_tree_code_skips_codes_of_soft_deleted_trees(): void
    {
        Tree::factory()->create(['code' => 'T-0001'])->delete();

        $this->assertSame('T-0002', Tree::factory()->create(['code' => null])->code);
    }

    public function test_author_is_filled_from_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tree = Tree::factory()->create();
        $careLog = CareLog::factory()->create(['user_id' => null]);

        $this->assertSame($user->id, $tree->created_by);
        $this->assertSame($user->id, $careLog->user_id);
    }

    public function test_sale_total_is_computed_by_the_server(): void
    {
        $sale = Sale::factory()->create(['quantity' => 2.5, 'unit_price' => 12000]);

        $this->assertSame('30000.00', $sale->total_amount);

        $sale->update(['quantity' => 3]);

        $this->assertSame('36000.00', $sale->fresh()->total_amount);
    }

    public function test_harvest_remaining_quantity_subtracts_sales(): void
    {
        $harvest = Harvest::factory()->create(['quantity' => 100]);
        Sale::factory()->for($harvest)->create(['quantity' => 30]);
        Sale::factory()->for($harvest)->create(['quantity' => 25.5]);

        $this->assertSame(44.5, $harvest->remainingQuantity());
        $this->assertSame(44.5, Harvest::withSum('sales', 'quantity')->find($harvest->id)->remainingQuantity());
    }

    public function test_photos_use_the_photos_disk(): void
    {
        Storage::fake('photos');

        $path = UploadedFile::fake()->image('tree.jpg')->store('trees', 'photos');
        $tree = Tree::factory()->create(['photo_path' => $path]);

        $this->assertNotNull($tree->photo_url);

        $replacement = UploadedFile::fake()->image('new.jpg')->store('trees', 'photos');
        $tree->update(['photo_path' => $replacement]);
        Storage::disk('photos')->assertMissing($path);

        $tree->forceDelete();
        Storage::disk('photos')->assertMissing($replacement);
    }

    public function test_tree_without_photo_has_no_url(): void
    {
        $this->assertNull(Tree::factory()->create(['photo_path' => null])->photo_url);
    }
}
