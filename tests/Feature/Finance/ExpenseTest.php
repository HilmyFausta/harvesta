<?php

namespace Tests\Feature\Finance;

use App\Enums\ExpenseCategory;
use App\Models\Block;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('expenses.index'))->assertRedirect(route('login'));
    }

    public function test_staff_cannot_open_expense_pages(): void
    {
        $expense = Expense::factory()->create();

        $this->actingAs(User::factory()->staff()->create());

        $this->get(route('expenses.index'))->assertForbidden();
        $this->get(route('expenses.create'))->assertForbidden();
        $this->get(route('expenses.edit', $expense))->assertForbidden();
    }

    public function test_admin_can_view_expenses(): void
    {
        $expense = Expense::factory()->create(['description' => 'Harvest crew wages']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Harvest crew wages')
            ->assertSee($expense->category->label());
    }

    public function test_admin_can_create_an_expense(): void
    {
        $admin = User::factory()->admin()->create();
        $block = Block::factory()->create();

        $this->actingAs($admin);

        Livewire::test('pages::expenses.create')
            ->set('block_id', (string) $block->id)
            ->set('category', ExpenseCategory::Labor->value)
            ->set('amount', '125000')
            ->set('spent_on', '2026-10-21')
            ->set('description', 'Harvest crew wages')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'block_id' => $block->id,
            'user_id' => $admin->id,
            'category' => ExpenseCategory::Labor->value,
            'description' => 'Harvest crew wages',
        ]);
    }

    public function test_expense_can_be_general_without_a_block(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::expenses.create')
            ->set('block_id', '')
            ->set('category', ExpenseCategory::Other->value)
            ->set('amount', '50000')
            ->set('spent_on', '2026-10-21')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'block_id' => null,
            'category' => ExpenseCategory::Other->value,
        ]);
    }

    public function test_expense_requires_valid_data(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::expenses.create')
            ->set('block_id', '999999')
            ->set('category', 'not-a-category')
            ->set('amount', '0')
            ->set('spent_on', 'not-a-date')
            ->call('save')
            ->assertHasErrors(['block_id', 'category', 'amount', 'spent_on']);
    }

    public function test_admin_can_update_an_expense(): void
    {
        $expense = Expense::factory()->create([
            'category' => ExpenseCategory::Water,
            'amount' => 100000,
        ]);
        $block = Block::factory()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::expenses.edit', ['expense' => $expense])
            ->set('block_id', (string) $block->id)
            ->set('category', ExpenseCategory::Equipment->value)
            ->set('amount', '275000')
            ->set('spent_on', '2026-10-20')
            ->set('description', 'Irrigation pump repair')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('expenses.index'));

        $expense->refresh();

        $this->assertSame($block->id, $expense->block_id);
        $this->assertSame(ExpenseCategory::Equipment, $expense->category);
        $this->assertSame('275000.00', $expense->amount);
        $this->assertSame('Irrigation pump repair', $expense->description);
    }

    public function test_admin_can_delete_an_expense(): void
    {
        $expense = Expense::factory()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::expenses.index')
            ->call('delete', $expense->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_filters_and_total_reflect_the_visible_expenses(): void
    {
        $block = Block::factory()->create();

        Expense::factory()->create([
            'block_id' => $block->id,
            'category' => ExpenseCategory::Fertilizer,
            'amount' => 100000,
            'description' => 'Seed fertilizer',
        ]);

        Expense::factory()->create([
            'block_id' => null,
            'category' => ExpenseCategory::Labor,
            'amount' => 250000,
            'description' => 'Harvest crew wages',
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::expenses.index')
            ->assertSee('Seed fertilizer')
            ->assertSee('Harvest crew wages')
            ->assertSee('IDR 350,000')
            ->set('category', ExpenseCategory::Fertilizer->value)
            ->assertSee('Seed fertilizer')
            ->assertDontSee('Harvest crew wages')
            ->assertSee('IDR 100,000')
            ->set('block_id', 'none')
            ->assertDontSee('Seed fertilizer')
            ->assertSee('No expenses found.');
    }

    public function test_search_matches_the_description(): void
    {
        Expense::factory()->create(['description' => 'Tractor fuel']);
        Expense::factory()->create(['description' => 'Pump repair']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::expenses.index')
            ->set('search', 'Tractor')
            ->assertSee('Tractor fuel')
            ->assertDontSee('Pump repair');
    }
}
