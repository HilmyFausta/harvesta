<?php

namespace Tests\Feature;

use App\Enums\TreeStatus;
use App\Models\Block;
use App\Models\CareLog;
use App\Models\Category;
use App\Models\Harvest;
use App\Models\Tree;
use App\Models\TreeType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class TreeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('photos');

        $this->admin = User::factory()->admin()->create();
        $this->staff = User::factory()->staff()->create();
    }

    /**
     * @return array<string, mixed>
     */
    protected function validInput(array $overrides = []): array
    {
        return [
            'form.tree_type_id' => (string) TreeType::factory()->create()->id,
            'form.block_id' => (string) Block::factory()->create()->id,
            'form.planted_on' => '2024-03-15',
            'form.status' => TreeStatus::Growing->value,
            'form.height_cm' => '180.5',
            'form.notes' => 'Planted near the irrigation canal.',
            ...$overrides,
        ];
    }

    protected function fillForm(string $component, array $input, array $params = []): Testable
    {
        $test = Livewire::test($component, $params);

        foreach ($input as $field => $value) {
            $test->set($field, $value);
        }

        return $test;
    }

    public function test_staff_can_view_the_tree_list_with_thumbnails(): void
    {
        $tree = Tree::factory()->create(['photo_path' => 'trees/sample.jpg']);

        $this->actingAs($this->staff)
            ->get(route('trees.index'))
            ->assertOk()
            ->assertSee($tree->code)
            ->assertSee($tree->photo_url)
            ->assertSee(route('trees.create'));
    }

    public function test_the_tree_list_has_no_n_plus_one_queries(): void
    {
        Tree::factory()->count(15)->create();

        $this->actingAs($this->staff);

        DB::enableQueryLog();
        Livewire::test('pages::trees.index');
        $queries = count(DB::getQueryLog());

        $this->assertLessThanOrEqual(12, $queries);
    }

    public function test_the_tree_list_can_be_searched_and_filtered(): void
    {
        $fruit = Category::factory()->create(['name' => 'Fruit']);
        $mango = TreeType::factory()->for($fruit)->create(['name' => 'Mango']);
        $north = Block::factory()->create(['name' => 'North Block']);

        $match = Tree::factory()->for($mango)->for($north)->status(TreeStatus::Productive)->create(['code' => 'T-0100', 'notes' => 'Sweet fruit']);
        $otherStatus = Tree::factory()->for($mango)->for($north)->status(TreeStatus::Seedling)->create(['code' => 'T-0101']);
        $otherType = Tree::factory()->status(TreeStatus::Productive)->create(['code' => 'T-0102']);

        $this->actingAs($this->staff);

        Livewire::test('pages::trees.index')
            ->set('status', TreeStatus::Productive->value)
            ->assertSee($match->code)
            ->assertDontSee($otherStatus->code)
            ->set('category_id', (string) $fruit->id)
            ->assertSee($match->code)
            ->assertDontSee($otherType->code)
            ->call('resetFilters')
            ->set('search', 'sweet')
            ->assertSee($match->code)
            ->assertDontSee($otherStatus->code)
            ->set('search', '')
            ->set('block_id', (string) $north->id)
            ->set('tree_type_id', (string) $mango->id)
            ->assertSee($otherStatus->code)
            ->assertDontSee($otherType->code);
    }

    public function test_the_tree_list_sorts_by_planting_date(): void
    {
        Tree::factory()->create(['code' => 'T-0001', 'planted_on' => '2020-01-01']);
        Tree::factory()->create(['code' => 'T-0002', 'planted_on' => '2018-01-01']);

        $this->actingAs($this->staff);

        Livewire::test('pages::trees.index')
            ->call('sortBy', 'planted_on')
            ->assertSeeInOrder(['T-0002', 'T-0001'])
            ->call('sortBy', 'planted_on')
            ->assertSeeInOrder(['T-0001', 'T-0002']);
    }

    public function test_staff_can_create_a_tree_with_a_generated_code_and_photo(): void
    {
        $this->actingAs($this->staff);

        $this->fillForm('pages::trees.create', $this->validInput([
            'form.photo' => UploadedFile::fake()->image('mango.jpg', 800, 600)->size(500),
        ]))
            ->call('save')
            ->assertHasNoErrors();

        $tree = Tree::sole();

        $this->assertSame('T-0001', $tree->code);
        $this->assertSame(TreeStatus::Growing, $tree->status);
        $this->assertSame($this->staff->id, $tree->created_by);
        $this->assertNotNull($tree->photo_path);
        Storage::disk('photos')->assertExists($tree->photo_path);
    }

    public function test_a_custom_code_must_be_unique_even_against_deleted_trees(): void
    {
        Tree::factory()->create(['code' => 'MANGO-01'])->delete();

        $this->actingAs($this->staff);

        $this->fillForm('pages::trees.create', $this->validInput(['form.code' => 'MANGO-01']))
            ->call('save')
            ->assertHasErrors(['form.code' => 'unique']);
    }

    public function test_tree_input_is_validated(): void
    {
        $this->actingAs($this->staff);

        $this->fillForm('pages::trees.create', $this->validInput([
            'form.planted_on' => today()->addDay()->format('Y-m-d'),
            'form.status' => 'flowering',
            'form.height_cm' => '-5',
            'form.tree_type_id' => '',
        ]))
            ->call('save')
            ->assertHasErrors([
                'form.planted_on' => 'before_or_equal',
                'form.status',
                'form.height_cm' => 'min',
                'form.tree_type_id' => 'required',
            ]);

        $this->assertSame(0, Tree::count());
    }

    public function test_photos_must_be_small_images(): void
    {
        $this->actingAs($this->staff);

        $this->fillForm('pages::trees.create', $this->validInput([
            'form.photo' => UploadedFile::fake()->image('huge.jpg')->size(3000),
        ]))
            ->call('save')
            ->assertHasErrors(['form.photo' => 'max']);

        $this->fillForm('pages::trees.create', $this->validInput([
            'form.photo' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ]))
            ->call('save')
            ->assertHasErrors(['form.photo']);

        $this->assertSame(0, Tree::count());
    }

    public function test_replacing_a_photo_deletes_the_old_file(): void
    {
        Storage::disk('photos')->put('trees/old.jpg', 'old');
        $tree = Tree::factory()->create(['photo_path' => 'trees/old.jpg']);

        $this->actingAs($this->staff);

        Livewire::test('pages::trees.edit', ['tree' => $tree])
            ->assertSet('form.code', $tree->code)
            ->set('form.status', TreeStatus::NeedsAttention->value)
            ->set('form.photo', UploadedFile::fake()->image('new.png'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('trees.show', $tree));

        $tree->refresh();

        $this->assertSame(TreeStatus::NeedsAttention, $tree->status);
        $this->assertNotSame('trees/old.jpg', $tree->photo_path);
        Storage::disk('photos')->assertMissing('trees/old.jpg');
        Storage::disk('photos')->assertExists($tree->photo_path);
    }

    public function test_a_photo_can_be_removed(): void
    {
        Storage::disk('photos')->put('trees/old.jpg', 'old');
        $tree = Tree::factory()->create(['photo_path' => 'trees/old.jpg']);

        $this->actingAs($this->staff);

        Livewire::test('pages::trees.edit', ['tree' => $tree])
            ->set('form.remove_photo', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($tree->fresh()->photo_path);
        Storage::disk('photos')->assertMissing('trees/old.jpg');
    }

    public function test_the_code_is_required_when_editing(): void
    {
        $tree = Tree::factory()->create();

        $this->actingAs($this->staff);

        Livewire::test('pages::trees.edit', ['tree' => $tree])
            ->set('form.code', '')
            ->call('save')
            ->assertHasErrors(['form.code' => 'required']);
    }

    public function test_the_detail_page_shows_care_logs_and_harvests(): void
    {
        $tree = Tree::factory()->status(TreeStatus::Productive)->create();
        CareLog::factory()->for($tree)->create(['notes' => 'Pruned the lower branches.']);
        Harvest::factory()->for($tree)->create(['notes' => 'First big harvest.']);

        $this->actingAs($this->staff)
            ->get(route('trees.show', $tree))
            ->assertOk()
            ->assertSee($tree->code)
            ->assertSee($tree->treeType->name)
            ->assertSee('Pruned the lower branches.')
            ->assertSee('First big harvest.')
            ->assertSee(route('care-logs.create', ['tree_id' => $tree->id]), false);
    }

    public function test_the_detail_page_warns_about_dead_trees(): void
    {
        $tree = Tree::factory()->dead()->create();

        $this->actingAs($this->staff)
            ->get(route('trees.show', $tree))
            ->assertSee('This tree is marked as dead.');
    }

    public function test_staff_cannot_delete_trees(): void
    {
        $tree = Tree::factory()->create();

        $this->actingAs($this->staff);

        Livewire::test('pages::trees.index')
            ->call('delete', $tree->id)
            ->assertForbidden();

        Livewire::test('pages::trees.show', ['tree' => $tree])
            ->call('delete')
            ->assertForbidden();

        $this->assertNotSoftDeleted($tree);
    }

    public function test_admin_soft_deletes_a_tree_and_keeps_its_photo(): void
    {
        Storage::disk('photos')->put('trees/keep.jpg', 'photo');
        $tree = Tree::factory()->create(['photo_path' => 'trees/keep.jpg']);

        $this->actingAs($this->admin);

        Livewire::test('pages::trees.show', ['tree' => $tree])
            ->call('delete')
            ->assertRedirect(route('trees.index'));

        $this->assertSoftDeleted($tree);
        Storage::disk('photos')->assertExists('trees/keep.jpg');

        $this->get(route('trees.show', $tree))->assertNotFound();
        $this->get(route('trees.index'))->assertDontSee($tree->code);
    }

    public function test_guests_cannot_open_tree_pages(): void
    {
        $tree = Tree::factory()->create();

        $this->get(route('trees.create'))->assertRedirect(route('login'));
        $this->get(route('trees.show', $tree))->assertRedirect(route('login'));
        $this->get(route('trees.edit', $tree))->assertRedirect(route('login'));
    }
}
