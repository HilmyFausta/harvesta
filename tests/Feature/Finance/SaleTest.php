<?php

namespace Tests\Feature\Finance;

use App\Models\Harvest;
use App\Models\Sale;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('sales.index'))->assertRedirect(route('login'));
    }

    public function test_staff_cannot_open_sale_pages(): void
    {
        $sale = Sale::factory()->create();

        $this->actingAs(User::factory()->staff()->create());

        $this->get(route('sales.index'))->assertForbidden();
        $this->get(route('sales.create'))->assertForbidden();
        $this->get(route('sales.edit', $sale))->assertForbidden();
    }

    public function test_admin_can_view_sales(): void
    {
        $sale = Sale::factory()->create(['buyer_name' => 'PT Harvest Jaya']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('sales.index'))
            ->assertOk()
            ->assertSee('PT Harvest Jaya')
            ->assertSee($sale->harvest->tree->code);
    }

    public function test_admin_can_create_a_sale(): void
    {
        $admin = User::factory()->admin()->create();
        $harvest = Harvest::factory()->create(['quantity' => 100]);

        $this->actingAs($admin);

        Livewire::test('pages::sales.create')
            ->set('harvest_id', (string) $harvest->id)
            ->set('buyer_name', 'PT Harvest Jaya')
            ->set('sold_on', '2026-10-21')
            ->set('quantity', '25.50')
            ->set('unit_price', '12000')
            ->set('notes', 'First delivery')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('sales', [
            'harvest_id' => $harvest->id,
            'user_id' => $admin->id,
            'buyer_name' => 'PT Harvest Jaya',
            'total_amount' => 306000,
        ]);
    }

    public function test_sale_quantity_cannot_exceed_the_remaining_harvest(): void
    {
        $harvest = Harvest::factory()->create(['quantity' => 10]);
        Sale::factory()->for($harvest)->create(['quantity' => 8]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::sales.create')
            ->set('harvest_id', (string) $harvest->id)
            ->set('buyer_name', 'PT Harvest Jaya')
            ->set('sold_on', '2026-10-21')
            ->set('quantity', '3')
            ->set('unit_price', '10000')
            ->call('save')
            ->assertHasErrors(['quantity']);

        $this->assertDatabaseCount('sales', 1);
    }

    public function test_admin_can_update_a_sale_without_counting_its_old_quantity(): void
    {
        $harvest = Harvest::factory()->create(['quantity' => 100]);
        $sale = Sale::factory()->for($harvest)->create([
            'quantity' => 60,
            'unit_price' => 10000,
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::sales.edit', ['sale' => $sale])
            ->set('quantity', '100')
            ->set('unit_price', '12000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('sales.index'));

        $this->assertSame('100.00', $sale->fresh()->quantity);
        $this->assertSame('1200000.00', $sale->fresh()->total_amount);
    }

    public function test_updating_a_sale_still_respects_other_sales(): void
    {
        $harvest = Harvest::factory()->create(['quantity' => 100]);
        $sale = Sale::factory()->for($harvest)->create(['quantity' => 60]);
        Sale::factory()->for($harvest)->create(['quantity' => 30]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::sales.edit', ['sale' => $sale])
            ->set('quantity', '75')
            ->call('save')
            ->assertHasErrors(['quantity']);

        $this->assertSame('60.00', $sale->fresh()->quantity);
    }

    public function test_a_sale_can_move_to_another_harvest(): void
    {
        $sale = Sale::factory()->create(['quantity' => 10]);
        $harvest = Harvest::factory()->create(['quantity' => 20]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::sales.edit', ['sale' => $sale])
            ->set('harvest_id', (string) $harvest->id)
            ->set('quantity', '20')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($harvest->id, $sale->fresh()->harvest_id);
    }

    public function test_sale_requires_valid_data(): void
    {
        $harvest = Harvest::factory()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::sales.create')
            ->set('harvest_id', '999999')
            ->set('buyer_name', '')
            ->set('sold_on', 'not-a-date')
            ->set('quantity', '0')
            ->set('unit_price', '0')
            ->call('save')
            ->assertHasErrors(['harvest_id', 'buyer_name', 'sold_on', 'quantity', 'unit_price']);
    }

    public function test_admin_can_delete_a_sale(): void
    {
        $sale = Sale::factory()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::sales.index')
            ->call('delete', $sale->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    public function test_filters_and_total_revenue_reflect_the_visible_sales(): void
    {
        $firstTree = Tree::factory()->create();
        $secondTree = Tree::factory()->create();
        $firstHarvest = Harvest::factory()->for($firstTree)->create(['quantity' => 100]);
        $secondHarvest = Harvest::factory()->for($secondTree)->create(['quantity' => 100]);

        Sale::factory()->for($firstHarvest)->create([
            'buyer_name' => 'PT Alpha',
            'sold_on' => '2026-10-10',
            'quantity' => 10,
            'unit_price' => 10000,
        ]);

        Sale::factory()->for($secondHarvest)->create([
            'buyer_name' => 'PT Beta',
            'sold_on' => '2026-10-15',
            'quantity' => 5,
            'unit_price' => 20000,
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::sales.index')
            ->assertSee('PT Alpha')
            ->assertSee('PT Beta')
            ->assertSee('IDR 200,000')
            ->set('buyer', 'Alpha')
            ->assertSee('PT Alpha')
            ->assertDontSee('PT Beta')
            ->assertSee('IDR 100,000')
            ->set('buyer', '')
            ->set('tree_type_id', (string) $secondTree->tree_type_id)
            ->assertSee('PT Beta')
            ->assertDontSee('PT Alpha')
            ->set('from', '2026-10-12')
            ->set('to', '2026-10-20')
            ->assertSee('PT Beta');
    }
}
