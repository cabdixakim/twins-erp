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
        // For weighted average costing, batch identity is irrelevant — every unit costs
        // the same average. No need to join batches or FIFO-order layers. Just query
        // depot_stocks directly and deduct in a stable order (oldest row first).
        $layers = DepotStock::query()
            ->where('company_id', $companyId)
            ->where('depot_id', $fromDepotId)
            ->where('product_id', $productId)
            ->whereRaw('(qty_on_hand - qty_reserved) > 0')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        $availableTotal = $layers->sum(fn($l) => max(0, (float) $l->qty_on_hand - (float) $l->qty_reserved));

        if ($availableTotal + 1e-9 < $qtyRequested) {
            throw new \RuntimeException('Insufficient stock in depot for this product.');
        }

        // Use the current depot+product weighted average cost
        $avgUnitCost = $this->currentWeightedAverageCost($companyId, $productId, $fromDepotId);
        $totalCost   = round($qtyRequested * $avgUnitCost, 2);

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
            'total_cost'    => $totalCost,
            'notes'         => $data['notes'] ?? null,
            'created_by'    => $data['created_by'] ?? null,
            'updated_by'    => $data['updated_by'] ?? ($data['created_by'] ?? null),
        ]);

        // Deduct qty_on_hand across layers and record one consumption per layer touched.
        // Use $totalCost (computed once from full qty) as the authoritative COGS figure
        // so that per-layer rounding never causes cumulative drift vs the movement total.
        $remaining      = $qtyRequested;
        $allocatedCost  = 0.0;
        $layerRecords   = [];

        foreach ($layers as $layer) {
            if ($remaining <= 0) break;

            $layerAvailable = max(0, (float) $layer->qty_on_hand - (float) $layer->qty_reserved);
            if ($layerAvailable <= 0) continue;

            $take = min($remaining, $layerAvailable);
            $layerRecords[] = ['layer' => $layer, 'take' => $take];
            $remaining -= $take;
        }

        $lastIdx = count($layerRecords) - 1;
        foreach ($layerRecords as $i => $rec) {
            $layer = $rec['layer'];
            $take  = $rec['take'];

            // Last layer absorbs any penny difference so sum of lines == $totalCost exactly
            if ($i === $lastIdx) {
                $lineTotal = round($totalCost - $allocatedCost, 2);
            } else {
                $lineTotal = round($take * $avgUnitCost, 2);
            }

            InventoryConsumption::create([
                'company_id'            => $companyId,
                'period_id'             => $period->id,
                'product_id'            => $productId,
                'type'                  => 'sale',
                'depot_id'              => $fromDepotId,
                'batch_id'              => $layer->batch_id ?: null,
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

            if ($layer->batch_id) {
                Batch::query()
                    ->where('company_id', $companyId)
                    ->whereKey((int) $layer->batch_id)
                    ->update([
                        'qty_remaining' => DB::raw('qty_remaining - ' . $take),
                        'updated_by'    => $data['updated_by'] ?? ($data['created_by'] ?? null),
                        'updated_at'    => now(),
                    ]);
            }

            $allocatedCost += $lineTotal;
        }

        // movement->total_cost was already set to $totalCost; no need to overwrite.
        return ['movement' => $movement, 'cogs_total' => $totalCost];
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
     * Post an ADJUSTMENT movement (stock reduction: shrinkage, write-off, etc.).
     *
     * Unit cost is taken from the current depot+product weighted average (or the
     * explicit unit_cost in $data if provided).  The weighted average is NOT
     * recalculated after a loss — the loss is expensed at current cost, remaining
     * stock keeps its value.  Call with reason_type:
     *   depot_shrinkage | write_off | meter_variance | stock_count_correction | transit_loss
     */
    public function adjustment(array $data, array $idempotencyWhere = []): InventoryMovement
    {
        return DB::transaction(function () use ($data, $idempotencyWhere) {
            $companyId = (int) $data['company_id'];
            $productId = (int) $data['product_id'];
            $depotId   = (int) $data['depot_id'];
            $batchId   = isset($data['batch_id']) && $data['batch_id'] ? (int) $data['batch_id'] : null;
            $qty       = abs((float) $data['qty']);

            $period = $this->postingGate->assertCanPost($companyId);

            if (!empty($idempotencyWhere)) {
                $existing = InventoryMovement::query()
                    ->where('company_id', $companyId)
                    ->where($idempotencyWhere)
                    ->first();
                if ($existing) return $existing;
            }

            $unitCost   = isset($data['unit_cost']) && $data['unit_cost'] > 0
                            ? (float) $data['unit_cost']
                            : $this->currentWeightedAverageCost($companyId, $productId, $depotId);
            $totalValue = round($qty * $unitCost, 4);

            $movement = InventoryMovement::create([
                'company_id'    => $companyId,
                'period_id'     => $period->id,
                'product_id'    => $productId,
                'type'          => 'adjustment',
                'ref_type'      => $data['ref_type'] ?? null,
                'ref_id'        => $data['ref_id'] ?? null,
                'reference'     => $data['reference'] ?? null,
                'batch_id'      => $batchId,
                'from_depot_id' => $depotId,
                'to_depot_id'   => null,
                'qty'           => -$qty,
                'unit_cost'     => $unitCost,
                'total_cost'    => -$totalValue,
                'notes'         => $data['notes'] ?? null,
                'created_by'    => $data['created_by'] ?? null,
                'updated_by'    => $data['updated_by'] ?? ($data['created_by'] ?? null),
            ]);

            // Reduce depot stock (specific batch or proportional across all layers)
            if ($batchId) {
                DepotStock::query()
                    ->where('company_id', $companyId)
                    ->where('depot_id', $depotId)
                    ->where('product_id', $productId)
                    ->where('batch_id', $batchId)
                    ->update([
                        'qty_on_hand' => DB::raw('GREATEST(0, qty_on_hand - ' . $qty . ')'),
                        'updated_by'  => $data['created_by'] ?? null,
                        'updated_at'  => now(),
                    ]);

                Batch::query()
                    ->where('company_id', $companyId)
                    ->whereKey($batchId)
                    ->update([
                        'qty_remaining' => DB::raw('GREATEST(0, qty_remaining - ' . $qty . ')'),
                        'updated_at'    => now(),
                    ]);
            } else {
                $layers    = DepotStock::query()
                    ->where('company_id', $companyId)
                    ->where('depot_id', $depotId)
                    ->where('product_id', $productId)
                    ->whereRaw('qty_on_hand > 0')
                    ->orderBy('id', 'asc')
                    ->get();

                $remaining = $qty;
                foreach ($layers as $layer) {
                    if ($remaining <= 0) break;
                    $take = min($remaining, (float) $layer->qty_on_hand);
                    DepotStock::query()->where('id', $layer->id)->update([
                        'qty_on_hand' => DB::raw('GREATEST(0, qty_on_hand - ' . $take . ')'),
                        'updated_at'  => now(),
                    ]);
                    if ($layer->batch_id) {
                        Batch::query()
                            ->where('company_id', $companyId)
                            ->whereKey((int) $layer->batch_id)
                            ->update([
                                'qty_remaining' => DB::raw('GREATEST(0, qty_remaining - ' . $take . ')'),
                                'updated_at'    => now(),
                            ]);
                    }
                    $remaining -= $take;
                }
            }

            // Financial loss record
            \App\Models\InventoryAdjustment::create([
                'company_id'            => $companyId,
                'period_id'             => $period->id,
                'product_id'            => $productId,
                'depot_id'              => $depotId,
                'batch_id'              => $batchId,
                'inventory_movement_id' => $movement->id,
                'reason_type'           => $data['reason_type'] ?? 'write_off',
                'qty'                   => $qty,
                'unit_cost'             => $unitCost,
                'total_value'           => $totalValue,
                'ref_type'              => $data['ref_type'] ?? null,
                'ref_id'                => $data['ref_id'] ?? null,
                'notes'                 => $data['notes'] ?? null,
                'created_by'            => $data['created_by'] ?? null,
                'updated_by'            => $data['updated_by'] ?? ($data['created_by'] ?? null),
            ]);

            return $movement;
        });
    }

    /**
     * Get the current weighted average unit cost for a depot+product.
     */
    public function currentWeightedAverageCost(int $companyId, int $productId, int $depotId): float
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

    /**
     * Recompute weighted average unit cost after a batch cost is added or removed.
     *
     * Called by BatchCostController after store() or destroy(). Only applies to
     * weighted_average companies — specific_lot costing is unaffected.
     *
     * Logic:
     *  1. Recompute this batch's unit_cost = (purchase_price × qty + total_landed_costs) / qty
     *  2. Update the depot_stock row for this batch to the new unit_cost
     *  3. Recompute the cross-batch weighted average for the depot+product
     *  4. Propagate the new average to all depot_stock rows for that depot+product
     */
    public static function recomputeUnitCostAfterBatchCostChange(\App\Models\Purchase $purchase): void
    {
        $company = DB::table('companies')->where('id', $purchase->company_id)->first();
        if (!$company) {
            return;
        }

        $costingMethod = $company->costing_method ?? 'weighted_average';

        if (!$purchase->batch_id || !$purchase->product_id) {
            return;
        }

        $companyId = (int) $purchase->company_id;
        $productId = (int) $purchase->product_id;
        $batchId   = (int) $purchase->batch_id;

        // Sum all landed costs for this purchase
        $totalLandedCosts = (float) DB::table('batch_costs')
            ->where('purchase_id', $purchase->id)
            ->sum('amount_base');

        // Total qty received across ALL depots for this batch
        $totalQtyReceived = (float) DB::table('inventory_movements')
            ->where('company_id', $companyId)
            ->where('batch_id', $batchId)
            ->where('type', 'receipt')
            ->sum('qty');

        if ($totalQtyReceived <= 0) {
            return; // Nothing received yet — nothing to recompute
        }

        // Determine which depots to recompute:
        //   - local_depot / cross_dock: purchase->depot_id is set → single depot
        //   - import: no depot_id → multiple trucks, each to its own depot; discover from movements
        if ($purchase->depot_id) {
            $depotIds = [(int) $purchase->depot_id];
        } else {
            $depotIds = DB::table('inventory_movements')
                ->where('company_id', $companyId)
                ->where('batch_id', $batchId)
                ->where('type', 'receipt')
                ->whereNotNull('to_depot_id')
                ->distinct()
                ->pluck('to_depot_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        foreach ($depotIds as $depotId) {
            // Qty received at this specific depot for this batch
            $depotQtyReceived = (float) DB::table('inventory_movements')
                ->where('company_id', $companyId)
                ->where('batch_id', $batchId)
                ->where('to_depot_id', $depotId)
                ->where('type', 'receipt')
                ->sum('qty');

            if ($depotQtyReceived <= 0) {
                continue;
            }

            // Option B costing: for import purchases, landed costs (freight/duty) are
            // charged on loaded qty per truck — spread over loaded qty, not delivered qty.
            // For local_depot / cross_dock there are no trucks, so delivered qty = loaded qty.
            if ($purchase->type === 'import') {
                // Find the trucks that delivered to this depot via their receipt movements
                $truckIds = DB::table('inventory_movements')
                    ->where('company_id', $companyId)
                    ->where('batch_id', $batchId)
                    ->where('to_depot_id', $depotId)
                    ->where('type', 'receipt')
                    ->where('ref_type', 'import_truck')
                    ->whereNotNull('ref_id')
                    ->pluck('ref_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                // Sum landed costs for those trucks specifically
                $depotLandedCosts = $truckIds
                    ? (float) DB::table('batch_costs')
                        ->where('purchase_id', $purchase->id)
                        ->whereIn('truck_id', $truckIds)
                        ->sum('amount_base')
                    : 0.0;

                // Sum loaded qty for those trucks (not delivered qty)
                $depotQtyLoaded = $truckIds
                    ? (float) DB::table('import_trucks')
                        ->whereIn('id', $truckIds)
                        ->sum('qty_loaded')
                    : $depotQtyReceived;

                $landedPerUnit = $depotQtyLoaded > 0 ? $depotLandedCosts / $depotQtyLoaded : 0.0;
                $newUnitCost   = round((float) $purchase->unit_price + $landedPerUnit, 6);
            } else {
                // local_depot / cross_dock: prorate landed costs by share of delivered qty
                $proratedLanded = $totalQtyReceived > 0
                    ? $totalLandedCosts * ($depotQtyReceived / $totalQtyReceived)
                    : 0.0;
                $newUnitCost    = round(((float) $purchase->unit_price * $depotQtyReceived + $proratedLanded) / $depotQtyReceived, 6);
            }

            // Update the depot_stock row for this batch+depot
            $stockRow = DepotStock::where('company_id', $companyId)
                ->where('depot_id', $depotId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
                ->first();

            if ($stockRow) {
                $stockRow->update(['unit_cost' => $newUnitCost, 'updated_at' => now()]);
            }

            // Always update the batch record itself
            DB::table('batches')
                ->where('id', $batchId)
                ->update(['unit_cost' => $newUnitCost, 'updated_at' => now()]);

            // For weighted average: recompute and propagate the cross-batch average
            // for this depot+product across all batches.
            // For specific_lot: each batch keeps its own unit_cost — no blending.
            if ($costingMethod === 'weighted_average') {
                $result = DepotStock::where('company_id', $companyId)
                    ->where('depot_id', $depotId)
                    ->where('product_id', $productId)
                    ->whereRaw('qty_on_hand > 0')
                    ->selectRaw('SUM(qty_on_hand) as total_qty, SUM(qty_on_hand * unit_cost) as total_value')
                    ->first();

                $totalQty   = (float) ($result->total_qty ?? 0);
                $totalValue = (float) ($result->total_value ?? 0);

                if ($totalQty > 0) {
                    $newAvg = round($totalValue / $totalQty, 6);
                    DepotStock::where('company_id', $companyId)
                        ->where('depot_id', $depotId)
                        ->where('product_id', $productId)
                        ->update(['unit_cost' => $newAvg, 'updated_at' => now()]);
                }
            }
        }
    }
}
