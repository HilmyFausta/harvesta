<?php

namespace App\Services;

use App\Models\Harvest;
use App\Models\Sale;
use App\Support\Format;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Encapsulates the remaining-quantity rule for harvest sales (PRD F-09).
 */
class SaleService
{
    /**
     * Get the quantity still available for a harvest.
     *
     * When editing a sale, pass it as $except so its current quantity is not
     * counted twice. A sale that is moving to another harvest is not excluded.
     */
    public function remainingQuantity(Harvest $harvest, ?Sale $except = null): float
    {
        $query = $harvest->sales();

        if ($except && $except->exists && (int) $except->harvest_id === (int) $harvest->id) {
            $query->whereKeyNot($except->getKey());
        }

        return round((float) $harvest->quantity - (float) $query->sum('quantity'), 2);
    }

    /**
     * Ensure the requested quantity fits in the unsold part of the harvest.
     *
     * @throws ValidationException
     */
    public function ensureQuantityAvailable(Harvest $harvest, float $quantity, ?Sale $except = null): void
    {
        $remaining = $this->remainingQuantity($harvest, $except);

        if (round($quantity, 2) > $remaining) {
            throw ValidationException::withMessages([
                'quantity' => __('messages.sale_exceeds_harvest', [
                    'remaining' => Format::number($remaining),
                    'unit' => $harvest->unit->label(),
                ]),
            ]);
        }
    }

    /**
     * Create a sale after validating the remaining harvest quantity.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data): Sale {
            $harvest = Harvest::query()->whereKey($data['harvest_id'])->lockForUpdate()->firstOrFail();

            $this->ensureQuantityAvailable($harvest, (float) $data['quantity']);

            return Sale::create($data);
        });
    }

    /**
     * Update a sale after validating the remaining harvest quantity.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data): Sale {
            $harvest = Harvest::query()->whereKey($data['harvest_id'])->lockForUpdate()->firstOrFail();

            $this->ensureQuantityAvailable($harvest, (float) $data['quantity'], $sale);

            $sale->update($data);

            return $sale;
        });
    }
}
