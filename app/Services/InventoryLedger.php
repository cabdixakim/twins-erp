<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\DepotStock;
use App\Models\InventoryConsumption;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class InventoryLedger
{
    /**
     * Post a RECEIPT movement and materialize it into depot_stocks + batch quantities.
     */
    public function receipt(array $data, array $idempotencyWhere = []): InventoryMovement
    {
        return DB::transaction(function () use ($data, $idempotencyWhere) {
            $companyId = (int) $data['company_id'];
            $productId = (int) $data['product_id'];
            $toDepotId = (int) $data['to_depot_id'];
            $batchId   = isset($data['batch_id']) ? (int) $data['batch_id'] : null;

            $qty   = (float) $data['qty'];
            $unit  = (float) ($data['unit_cost'] ?? 0);
            $total = isset($data['total_cost']) ? (float) $data['total_cost'] : round($qty * $unit, 2);

            // Optional idempotency guard
            if (!empty($idempotencyWhere)) {
                $exists = InventoryMovement::query()
                    ->where('company_id', $companyId)
                    ->where($idempotencyWhere)
                    ->exists();

                if ($exists) {
                    return InventoryMovement::query()
                        ->where('company_id', $companyId)
                        ->where($idempotencyWhere)
                        ->latest('id')
                        ->first();
                }
            }

            $movement = InventoryMovement::create([
                'company_id'    => $companyId,
                'product_id'    => $productId,
                'type'          => 'receipt',
                'ref_type'      => $data['ref_type'] ?? null,
                'ref_id'        => $data['ref_id'] ?? null,
                'reference'     => $data['reference'] ?? null,
                'batch_id'      => $batchId,
                'from_depot_id' => $data['from_depot_id'] ?? null,
                'to_depot_id'   => $toDepotId,
                'qty'           => $qty,
                'unit_cost'     => $unit,
                'total_cost'    => $total,
                'notes'         => $data['notes'] ?? null,
                'created_by'    => $data['created_by'] ?? null,
                'updated_by'    => $data['updated_by'] ?? ($data['created_by'] ?? null),
            ]);

            // Update batch quantities (receipt means it becomes available to sell)
            if ($batchId) {
                Batch::query()
                    ->where('company_id', $companyId)
                    ->whereKey($batchId)
                    ->update([
                        'qty_received'  => DB::raw('qty_received + ' . $qty),
                        'qty_remaining' => DB::raw('qty_remaining + ' . $qty),
                        'updated_by'    => $data['updated_by'] ?? ($data['created_by'] ?? null),
                        'updated_at'    => now(),
                    ]);
            }

            // Upsert depot stock snapshot (batch-aware)
            $stock = DepotStock::query()->firstOrNew([
                'company_id' => $companyId,
                'depot_id'   => $toDepotId,
                'product_id' => $productId,
                'batch_id'   => $batchId,
            ]);

            if (!$stock->exists) {
                $stock->qty_on_hand  = 0;
                $stock->qty_reserved = 0;
                $stock->unit_cost    = $unit;
                $stock->created_by   = $data['created_by'] ?? null;
            }

            $stock->qty_on_hand = (float) $stock->qty_on_hand + $qty;
            $stock->unit_cost   = $unit;
            $stock->updated_by  = $data['updated_by'] ?? ($data['created_by'] ?? null);
            $stock->save();

            return $movement;
        });
    }

    /**
     * Post an ISSUE movement using FIFO allocation from depot_stocks layers.
     * Returns: ['movement' => InventoryMovement, 'cogs_total' => float]
     */
   public function issue(array $data, array $idempotencyWhere = []): array
{
    return DB::transaction(function () use ($data, $idempotencyWhere) {

        $companyId   = (int) $data['company_id'];
        $productId   = (int) $data['product_id'];
        $fromDepotId = (int) $data['from_depot_id'];

        $qtyRequested = (float) ($data['qty'] ?? 0);
        if ($qtyRequested <= 0) {
            throw new \InvalidArgumentException('Issue qty must be > 0.');
        }

        // --- Idempotency: if already issued, return it (movement + cogs sum) ---
        if (!empty($idempotencyWhere)) {

            // Stronger guard: always include these (prevents accidental cross-matches)
            $mustMatch = [
                'type'          => 'issue',
                'product_id'    => $productId,
                'from_depot_id' => $fromDepotId,
            ];

            $existing = InventoryMovement::query()
                ->where('company_id', $companyId)
                ->where($mustMatch)
                ->where($idempotencyWhere)
                ->orderBy('id', 'asc')
                ->first();

            if ($existing) {
                $cogs = (float) InventoryConsumption::query()
                    ->where('company_id', $companyId)
                    ->where('inventory_movement_id', $existing->id)
                    ->sum('total_cost');

                return ['movement' => $existing, 'cogs_total' => $cogs];
            }
        }

        // --- FIFO layers (batch-aware) ---
        $layers = DepotStock::query()
            ->where('depot_stocks.company_id', $companyId)
            ->where('depot_stocks.depot_id', $fromDepotId)
            ->where('depot_stocks.product_id', $productId)
            ->whereRaw('(depot_stocks.qty_on_hand - depot_stocks.qty_reserved) > 0')
            ->join('batches', function ($j) use ($companyId) {
                $j->on('batches.id', '=', 'depot_stocks.batch_id')
                  ->where('batches.company_id', '=', $companyId);
            })
            ->orderBy('batches.purchased_at', 'asc')
            ->orderBy('depot_stocks.batch_id', 'asc')
            ->orderBy('depot_stocks.id', 'asc') // deterministic tie-break
            ->select('depot_stocks.*', 'batches.purchased_at')
            ->lockForUpdate()
            ->get();

        $availableTotal = 0.0;
        foreach ($layers as $l) {
            $availableTotal += max(0, (float) $l->qty_on_hand - (float) $l->qty_reserved);
        }

        if ($availableTotal + 1e-9 < $qtyRequested) {
            throw new \RuntimeException('Insufficient stock in depot for this product.');
        }

        // --- Create issue movement first (we fill cost after FIFO allocation) ---
        $movement = InventoryMovement::create([
            'company_id'    => $companyId,
            'product_id'    => $productId,
            'type'          => 'issue',
            'ref_type'      => $data['ref_type'] ?? null,
            'ref_id'        => $data['ref_id'] ?? null,
            'reference'     => $data['reference'] ?? null,
            'batch_id'      => null, // multi-batch allocation
            'from_depot_id' => $fromDepotId,
            'to_depot_id'   => $data['to_depot_id'] ?? null,
            'qty'           => $qtyRequested,
            'unit_cost'     => 0,
            'total_cost'    => 0,
            'notes'         => $data['notes'] ?? null,
            'created_by'    => $data['created_by'] ?? null,
            'updated_by'    => $data['updated_by'] ?? ($data['created_by'] ?? null),
        ]);

        $remaining = $qtyRequested;
        $cogsTotal = 0.0;

        foreach ($layers as $layer) {
            if ($remaining <= 0) break;

            $layerAvailable = max(0, (float) $layer->qty_on_hand - (float) $layer->qty_reserved);
            if ($layerAvailable <= 0) continue;

            $take     = min($remaining, $layerAvailable);
            $unitCost = (float) $layer->unit_cost;
            $lineTotal = round($take * $unitCost, 2);

            // --- Consumption line (per batch) ---
            InventoryConsumption::create([
                'company_id'            => $companyId,
                'product_id'            => $productId,
                'type'                  => 'sale', // your table shows a "type" column; keep consistent
                'depot_id'              => $fromDepotId,
                'batch_id'              => (int) $layer->batch_id,
                'inventory_movement_id' => $movement->id,

                // These exist in your table screenshot - set them (helps exports later)
                'ref_type'              => $data['ref_type'] ?? null,
                'ref_id'                => $data['ref_id'] ?? null,
                'reference'             => $data['reference'] ?? null,

                'qty'                   => $take,
                'unit_cost'             => $unitCost,
                'total_cost'            => $lineTotal,
                'notes'                 => $data['notes'] ?? null,
                'created_by'            => $data['created_by'] ?? null,
                'updated_by'            => $data['updated_by'] ?? ($data['created_by'] ?? null),
            ]);

            // --- Reduce depot stock (this layer) ---
            DepotStock::query()
                ->where('company_id', $companyId)
                ->where('id', (int) $layer->id)
                ->where('depot_id', $fromDepotId)
                ->where('product_id', $productId)
                ->where('batch_id', (int) $layer->batch_id)
                ->update([
                    'qty_on_hand' => DB::raw('qty_on_hand - ' . $take),
                    'updated_by'  => $data['updated_by'] ?? ($data['created_by'] ?? null),
                    'updated_at'  => now(),
                ]);

            // --- Reduce batch remaining ---
            Batch::query()
                ->where('company_id', $companyId)
                ->whereKey((int) $layer->batch_id)
                ->update([
                    'qty_remaining' => DB::raw('qty_remaining - ' . $take),
                    'updated_by'    => $data['updated_by'] ?? ($data['created_by'] ?? null),
                    'updated_at'    => now(),
                ]);

            $cogsTotal += $lineTotal;
            $remaining -= $take;
        }

        $avgUnit = $qtyRequested > 0 ? round($cogsTotal / $qtyRequested, 6) : 0;

        $movement->unit_cost  = $avgUnit;
        $movement->total_cost = round($cogsTotal, 2);
        $movement->updated_by = $data['updated_by'] ?? ($data['created_by'] ?? null);
        $movement->save();

        return ['movement' => $movement, 'cogs_total' => (float) $movement->total_cost];
    });
}
}