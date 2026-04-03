<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\DepotStock;
use App\Models\InventoryConsumption;
use App\Models\InventoryMovement;
use App\Models\InventoryPeriod;
use Illuminate\Support\Facades\DB;

class InventoryLedger
{
    public function __construct(
        protected PostingGate $postingGate
    ) {}

    /**
     * Post a RECEIPT movement.
     *
     * Costing behaviour:
     *   weighted_average → recalculates depot+product average after this receipt
     *   specific_lot     → unit cost is fixed at the value provided; never recalculated
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

            // Gate: paused or no open period → throws RuntimeException
            $period        = $this->postingGate->assertCanPost($companyId);
            $costingMethod = $period->costing_method;

            // Idempotency guard
            if (!empty($idempotencyWhere)) {
                $existing = InventoryMovement::query()
                    ->where('company_id', $companyId)
                    ->where($idempotencyWhere)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            // For weighted average: compute new average unit cost after this receipt
            if ($costingMethod === 'weighted_average') {
                $unit = $this->computeWeightedAverageAfterReceipt(
                    $companyId, $productId, $toDepotId, $qty, $unit
                );
                $total = round($qty * $unit, 2);
            }

            $movement = InventoryMovement::create([
                'company_id'    => $companyId,
                'period_id'     => $period->id,
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

            // Update batch quantities
            if ($batchId) {
                $batchUpdate = [
                    'qty_received'  => DB::raw('qty_received + ' . $qty),
                    'qty_remaining' => DB::raw('qty_remaining + ' . $qty),
                    'updated_by'    => $data['updated_by'] ?? ($data['created_by'] ?? null),
                    'updated_at'    => now(),
                ];

                // For specific_lot, lock in the batch unit cost at receipt time
                if ($costingMethod === 'specific_lot') {
                    $batchUpdate['unit_cost']  = $unit;
                    $batchUpdate['total_cost'] = DB::raw('qty_remaining * ' . $unit);
                }

                Batch::query()
                    ->where('company_id', $companyId)
                    ->whereKey($batchId)
                    ->update($batchUpdate);
            }

            // Upsert depot stock snapshot
            $stock = DepotStock::query()->firstOrNew([
                'company_id' => $companyId,
                'depot_id'   => $toDepotId,
                'product_id' => $productId,
                'batch_id'   => $batchId,
            ]);

            if (!$stock->exists) {
                $stock->qty_on_hand  = 0;
                $stock->qty_reserved = 0;
                $stock->created_by   = $data['created_by'] ?? null;
            }

            $stock->qty_on_hand = (float) $stock->qty_on_hand + $qty;
            $stock->unit_cost   = $unit;
            $stock->updated_by  = $data['updated_by'] ?? ($data['created_by'] ?? null);
            $stock->save();

            // For weighted average: update the unit cost on all other depot_stock rows
            // for this depot+product to reflect the new average
            if ($costingMethod === 'weighted_average') {
                $this->propagateWeightedAverageCost($companyId, $productId, $toDepotId, $unit, $stock->id);
            }

            return $movement;
        });
    }

    /**
     * Post an ISSUE movement.
     *
     * Costing behaviour:
     *   weighted_average → uses current depot+product average cost; no batch preference
     *   specific_lot     → caller must supply batch_id; cost taken from that batch
     *
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

            // Gate check
            $period        = $this->postingGate->assertCanPost($companyId);
            $costingMethod = $period->costing_method;

            // Idempotency guard
            if (!empty($idempotencyWhere)) {
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

            if ($costingMethod === 'weighted_average') {
                return $this->issueWeightedAverage(
                    $data, $period, $companyId, $productId, $fromDepotId, $qtyRequested
                );
            }

            return $this->issueSpecificLot(
                $data, $period, $companyId, $productId, $fromDepotId, $qtyRequested
            );
        });
    }

    // -------------------------------------------------------------------------
    // Weighted Average Issue
    // -------------------------------------------------------------------------

    private function issueWeightedAverage(
        array $data,
        InventoryPeriod $period,
        int $companyId,
        int $productId,
        int $fromDepotId,
        float $qtyRequested
    ): array {
        // Get all available layers (ordered by batch, deterministic)
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
            ->orderBy('depot_stocks.id', 'asc')
            ->select('depot_stocks.*', 'batches.purchased_at')
            ->lockForUpdate()
            ->get();

        $availableTotal = $layers->sum(fn($l) => max(0, (float) $l->qty_on_hand - (float) $l->qty_reserved));

        if ($availableTotal + 1e-9 < $qtyRequested) {
            throw new \RuntimeException('Insufficient stock in depot for this product.');
        }

        // Compute current weighted average cost for this depot+product
        $avgUnitCost = $this->currentWeightedAverageCost($companyId, $productId, $fromDepotId);

        $movement = InventoryMovement::create([
            'company_id'    => $companyId,
            'period_id'     => $period->id,
            'product_id'    => $productId,
            'type'          => 'issue',
            'ref_type'      => $data['ref_type'] ?? null,
            'ref_id'        => $data['ref_id'] ?? null,
            'reference'     => $data['reference'] ?? null,
            'batch_id'      => null,
            'from_depot_id' => $fromDepotId,
            'to_depot_id'   => $data['to_depot_id'] ?? null,
            'qty'           => $qtyRequested,
            'unit_cost'     => $avgUnitCost,
            'total_cost'    => round($qtyRequested * $avgUnitCost, 2),
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

            $take      = min($remaining, $layerAvailable);
            $lineTotal = round($take * $avgUnitCost, 2);

            InventoryConsumption::create([
                'company_id'            => $companyId,
                'period_id'             => $period->id,
                'product_id'            => $productId,
                'type'                  => 'sale',
                'depot_id'              => $fromDepotId,
                'batch_id'              => (int) $layer->batch_id,
                'inventory_movement_id' => $movement->id,
                'ref_type'              => $data['ref_type'] ?? null,
                'ref_id'                => $data['ref_id'] ?? null,
                'reference'             => $data['reference'] ?? null,
                'qty'                   => $take,
                'unit_cost'             => $avgUnitCost,
                'total_cost'            => $lineTotal,
                'notes'                 => $data['notes'] ?? null,
                'created_by'            => $data['created_by'] ?? null,
                'updated_by'            => $data['updated_by'] ?? ($data['created_by'] ?? null),
            ]);

            DepotStock::query()
                ->where('company_id', $companyId)
                ->where('id', (int) $layer->id)
                ->update([
                    'qty_on_hand' => DB::raw('qty_on_hand - ' . $take),
                    'updated_by'  => $data['updated_by'] ?? ($data['created_by'] ?? null),
                    'updated_at'  => now(),
                ]);

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

        $movement->total_cost = round($cogsTotal, 2);
        $movement->save();

        return ['movement' => $movement, 'cogs_total' => (float) $movement->total_cost];
    }

    // -------------------------------------------------------------------------
    // Specific Lot Issue
    // -------------------------------------------------------------------------

    private function issueSpecificLot(
        array $data,
        InventoryPeriod $period,
        int $companyId,
        int $productId,
        int $fromDepotId,
        float $qtyRequested
    ): array {
        $batchId = isset($data['batch_id']) ? (int) $data['batch_id'] : null;

        if (!$batchId) {
            throw new \InvalidArgumentException(
                'A specific batch must be selected when using specific lot costing.'
            );
        }

        $layer = DepotStock::query()
            ->where('company_id', $companyId)
            ->where('depot_id', $fromDepotId)
            ->where('product_id', $productId)
            ->where('batch_id', $batchId)
            ->lockForUpdate()
            ->firstOrFail();

        $available = max(0, (float) $layer->qty_on_hand - (float) $layer->qty_reserved);

        if ($available + 1e-9 < $qtyRequested) {
            throw new \RuntimeException('Insufficient stock in the selected batch for this depot.');
        }

        $unitCost  = (float) $layer->unit_cost;
        $lineTotal = round($qtyRequested * $unitCost, 2);

        $movement = InventoryMovement::create([
            'company_id'    => $companyId,
            'period_id'     => $period->id,
            'product_id'    => $productId,
            'type'          => 'issue',
            'ref_type'      => $data['ref_type'] ?? null,
            'ref_id'        => $data['ref_id'] ?? null,
            'reference'     => $data['reference'] ?? null,
            'batch_id'      => $batchId,
            'from_depot_id' => $fromDepotId,
            'to_depot_id'   => $data['to_depot_id'] ?? null,
            'qty'           => $qtyRequested,
            'unit_cost'     => $unitCost,
            'total_cost'    => $lineTotal,
            'notes'         => $data['notes'] ?? null,
            'created_by'    => $data['created_by'] ?? null,
            'updated_by'    => $data['updated_by'] ?? ($data['created_by'] ?? null),
        ]);

        InventoryConsumption::create([
            'company_id'            => $companyId,
            'period_id'             => $period->id,
            'product_id'            => $productId,
            'type'                  => 'sale',
            'depot_id'              => $fromDepotId,
            'batch_id'              => $batchId,
            'inventory_movement_id' => $movement->id,
            'ref_type'              => $data['ref_type'] ?? null,
            'ref_id'                => $data['ref_id'] ?? null,
            'reference'             => $data['reference'] ?? null,
            'qty'                   => $qtyRequested,
            'unit_cost'             => $unitCost,
            'total_cost'            => $lineTotal,
            'notes'                 => $data['notes'] ?? null,
            'created_by'            => $data['created_by'] ?? null,
            'updated_by'            => $data['updated_by'] ?? ($data['created_by'] ?? null),
        ]);

        DepotStock::query()
            ->where('company_id', $companyId)
            ->where('id', $layer->id)
            ->update([
                'qty_on_hand' => DB::raw('qty_on_hand - ' . $qtyRequested),
                'updated_by'  => $data['updated_by'] ?? ($data['created_by'] ?? null),
                'updated_at'  => now(),
            ]);

        Batch::query()
            ->where('company_id', $companyId)
            ->whereKey($batchId)
            ->update([
                'qty_remaining' => DB::raw('qty_remaining - ' . $qtyRequested),
                'updated_by'    => $data['updated_by'] ?? ($data['created_by'] ?? null),
                'updated_at'    => now(),
            ]);

        return ['movement' => $movement, 'cogs_total' => $lineTotal];
    }

    // -------------------------------------------------------------------------
    // Weighted Average Helpers
    // -------------------------------------------------------------------------

    /**
     * Compute the new weighted average unit cost for a depot+product
     * after receiving qty units at incomingUnit cost.
     */
    private function computeWeightedAverageAfterReceipt(
        int $companyId,
        int $productId,
        int $depotId,
        float $incomingQty,
        float $incomingUnit
    ): float {
        $existing = DepotStock::query()
            ->where('company_id', $companyId)
            ->where('depot_id', $depotId)
            ->where('product_id', $productId)
            ->selectRaw('SUM(qty_on_hand) as total_qty, SUM(qty_on_hand * unit_cost) as total_value')
            ->first();

        $existingQty   = (float) ($existing->total_qty ?? 0);
        $existingValue = (float) ($existing->total_value ?? 0);

        $newQty   = $existingQty + $incomingQty;
        $newValue = $existingValue + ($incomingQty * $incomingUnit);

        if ($newQty <= 0) {
            return $incomingUnit;
        }

        return round($newValue / $newQty, 6);
    }

    /**
     * Get the current weighted average unit cost for a depot+product.
     */
    private function currentWeightedAverageCost(int $companyId, int $productId, int $depotId): float
    {
        $result = DepotStock::query()
            ->where('company_id', $companyId)
            ->where('depot_id', $depotId)
            ->where('product_id', $productId)
            ->whereRaw('qty_on_hand > 0')
            ->selectRaw('SUM(qty_on_hand) as total_qty, SUM(qty_on_hand * unit_cost) as total_value')
            ->first();

        $qty   = (float) ($result->total_qty ?? 0);
        $value = (float) ($result->total_value ?? 0);

        return $qty > 0 ? round($value / $qty, 6) : 0;
    }

    /**
     * After a weighted average receipt, update unit_cost on all existing depot_stock
     * rows for this depot+product to the new average (so the next issue is correct).
     */
    private function propagateWeightedAverageCost(
        int $companyId,
        int $productId,
        int $depotId,
        float $newAvgCost,
        int $excludeStockId
    ): void {
        DepotStock::query()
            ->where('company_id', $companyId)
            ->where('depot_id', $depotId)
            ->where('product_id', $productId)
            ->where('id', '!=', $excludeStockId)
            ->update(['unit_cost' => $newAvgCost, 'updated_at' => now()]);
    }
}
