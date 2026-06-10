<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Batch;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\TransporterLedgerEntry;
use App\Services\InventoryLedger;
use App\Http\Controllers\SupplierLedgerController;

class SeedDemoBatch extends Command
{
    protected $signature   = 'demo:seed-batch {--fresh : Drop all demo data first}';
    protected $description = 'Create one complete batch lifecycle: purchase → depot receipt → sale → invoice → payments';

    public function handle(InventoryLedger $ledger): int
    {
        // ── Auth context ───────────────────────────────────────────────────
        $user = DB::table('users')->where('email', 'kim@twins.com')->first();
        if (!$user) {
            $this->error('User kim@twins.com not found.');
            return 1;
        }
        Auth::loginUsingId($user->id);
        $uid = $user->id;

        // ── Resolve company ────────────────────────────────────────────────
        $cid = DB::table('company_user')
            ->where('user_id', $uid)
            ->value('company_id');
        if (!$cid) {
            $this->error('No company linked to kim@twins.com.');
            return 1;
        }

        $companyName = DB::table('companies')->where('id', $cid)->value('name');
        $this->info("Company: {$companyName} (id={$cid})");

        // ── Resolve product ────────────────────────────────────────────────
        $product = DB::table('products')->where('company_id', $cid)->first();
        if (!$product) {
            $this->error('No products found. Add one in Settings → Products first.');
            return 1;
        }
        $pid = $product->id;
        $this->info("Product: {$product->name} (id={$pid})");

        // ── Resolve supplier ───────────────────────────────────────────────
        $supplier = DB::table('suppliers')->where('company_id', $cid)->first();
        if (!$supplier) {
            $this->error('No supplier found. Add one in Settings → Suppliers first.');
            return 1;
        }
        $supplierId = $supplier->id;

        // ── Resolve transporter ────────────────────────────────────────────
        $transporter = DB::table('transporters')->where('company_id', $cid)->first();
        $transporterId = $transporter?->id;

        // ── Fresh wipe of demo data ────────────────────────────────────────
        if ($this->option('fresh')) {
            $this->wipe($cid);
        }

        // ═══════════════════════════════════════════════════════════════════
        // 1 — DEPOT
        // ═══════════════════════════════════════════════════════════════════
        $depotId = DB::table('depots')
            ->where('company_id', $cid)
            ->where('name', 'Kinshasa Terminal')
            ->where('is_system', false)
            ->value('id');

        if (!$depotId) {
            $depotId = DB::table('depots')->insertGetId([
                'company_id'             => $cid,
                'name'                   => 'Kinshasa Terminal',
                'city'                   => 'Kinshasa',
                'is_active'              => true,
                'is_system'              => false,
                'default_currency'       => 'USD',
                'storage_fee_per_1000_l' => 2.50,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
            $this->info("✓ Depot created: Kinshasa Terminal (id={$depotId})");
        } else {
            $this->info("→ Depot exists: Kinshasa Terminal (id={$depotId})");
        }

        // ═══════════════════════════════════════════════════════════════════
        // 2 — CLIENT
        // ═══════════════════════════════════════════════════════════════════
        $clientId = DB::table('clients')
            ->where('company_id', $cid)
            ->where('code', 'GLC-001')
            ->value('id');

        if (!$clientId) {
            $clientId = DB::table('clients')->insertGetId([
                'company_id'     => $cid,
                'name'           => 'Glencore DRC',
                'code'           => 'GLC-001',
                'type'           => 'corporate',
                'country'        => 'DRC',
                'city'           => 'Kinshasa',
                'contact_person' => 'Jean-Pierre Mbeki',
                'phone'          => '+243 81 234 5678',
                'email'          => 'jp.mbeki@glencore-drc.com',
                'currency'       => 'USD',
                'credit_limit'   => 500000,
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $this->info("✓ Client created: Glencore DRC (id={$clientId})");
        } else {
            $this->info("→ Client exists: Glencore DRC (id={$clientId})");
        }

        // ═══════════════════════════════════════════════════════════════════
        // 3 — PURCHASE  (local depot, draft)
        // ═══════════════════════════════════════════════════════════════════
        $this->line('');
        $this->info('── PURCHASE ──────────────────────────────────────────');

        $purchaseQty   = 500_000;   // litres
        $purchasePrice = 0.720;     // USD/L
        $freightAmt    = 4_500;     // USD flat freight
        $purchaseTotal = round($purchaseQty * $purchasePrice, 2);

        $ref = 'PO-KIN-' . now()->format('Y') . '-0001';

        $purchaseId = DB::table('purchases')->where('company_id', $cid)->where('reference', $ref)->value('id');
        if (!$purchaseId) {
            $purchaseId = DB::table('purchases')->insertGetId([
                'company_id'        => $cid,
                'type'              => 'local_depot',
                'supplier_id'       => $supplierId,
                'product_id'        => $pid,
                'depot_id'          => $depotId,
                'purchase_date'     => now()->subDays(12)->toDateString(),
                'qty'               => $purchaseQty,
                'unit_price'        => $purchasePrice,
                'currency'          => 'USD',
                'status'            => 'draft',
                'reference'         => $ref,
                'transporter_id'    => $transporterId,
                'freight_amount'    => $freightAmt,
                'freight_currency'  => 'USD',
                'notes'             => 'Demo batch — AGO 500KL from Petro Seven',
                'created_by'        => $uid,
                'updated_by'        => $uid,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        $purchase = Purchase::find($purchaseId);
        $this->info("Purchase {$ref} — status: {$purchase->status}");

        // ── 3a Confirm ─────────────────────────────────────────────────────
        if ($purchase->status === 'draft') {
            DB::transaction(function () use ($purchase, $uid, $purchaseQty, $purchasePrice) {
                $code  = 'BATCH-' . now()->format('Y') . '-AGO01';
                $batch = Batch::create([
                    'company_id'    => $purchase->company_id,
                    'product_id'    => $purchase->product_id,
                    'source_type'   => 'local_depot',
                    'source_ref'    => 'purchase:' . $purchase->id,
                    'code'          => $code,
                    'name'          => 'AGO Batch Jun 2026',
                    'supplier_id'   => $purchase->supplier_id,
                    'qty_purchased' => $purchaseQty,
                    'qty_received'  => 0,
                    'qty_remaining' => 0,
                    'total_cost'    => round($purchaseQty * $purchasePrice, 2),
                    'unit_cost'     => $purchasePrice,
                    'status'        => 'active',
                    'purchased_at'  => now()->subDays(12),
                    'created_by'    => $uid,
                    'updated_by'    => $uid,
                ]);
                $purchase->batch_id  = $batch->id;
                $purchase->status    = 'confirmed';
                $purchase->updated_by = $uid;
                $purchase->save();
            });
            $purchase->refresh();
            $this->info("✓ Purchase confirmed — Batch created");
        }

        // ── 3b Receive ─────────────────────────────────────────────────────
        if ($purchase->status === 'confirmed') {
            DB::transaction(function () use ($purchase, $ledger, $uid, $freightAmt, $transporterId, $cid, $supplierId, $purchaseQty, $purchasePrice) {
                $ledger->receipt(
                    [
                        'company_id'  => (int) $purchase->company_id,
                        'product_id'  => (int) $purchase->product_id,
                        'to_depot_id' => (int) $purchase->depot_id,
                        'batch_id'    => (int) $purchase->batch_id,
                        'qty'         => (float) $purchase->qty,
                        'unit_cost'   => (float) $purchase->unit_price,
                        'total_cost'  => round((float) $purchase->qty * (float) $purchase->unit_price, 2),
                        'ref_type'    => 'purchase',
                        'ref_id'      => (int) $purchase->id,
                        'reference'   => 'purchase:' . $purchase->id,
                        'notes'       => 'Demo batch receipt into Kinshasa Terminal',
                        'created_by'  => $uid,
                        'updated_by'  => $uid,
                    ],
                    ['type' => 'receipt', 'ref_type' => 'purchase', 'ref_id' => (int) $purchase->id,
                     'batch_id' => (int) $purchase->batch_id, 'to_depot_id' => (int) $purchase->depot_id]
                );

                $purchase->status     = 'received';
                $purchase->updated_by = $uid;
                $purchase->save();

                // Supplier invoice
                SupplierLedgerController::postInvoice(
                    companyId:   $cid,
                    supplierId:  $supplierId,
                    amount:      round($purchaseQty * $purchasePrice, 2),
                    currency:    'USD',
                    description: "Purchase {$purchase->reference} — 500,000L AGO received",
                    entryDate:   now()->subDays(12)->toDateString(),
                    refType:     'purchase',
                    refId:       (int) $purchase->id,
                    createdBy:   $uid,
                );

                // Freight charge on transporter ledger
                if ($transporterId && $freightAmt > 0) {
                    $exists = TransporterLedgerEntry::where('ref_type', 'purchase')
                        ->where('ref_id', $purchase->id)
                        ->where('type', 'freight_charge')
                        ->exists();
                    if (!$exists) {
                        TransporterLedgerEntry::create([
                            'company_id'     => $cid,
                            'transporter_id' => $transporterId,
                            'type'           => 'freight_charge',
                            'amount'         => $freightAmt,
                            'currency'       => 'USD',
                            'description'    => "Freight for {$purchase->reference}",
                            'entry_date'     => now()->subDays(12)->toDateString(),
                            'ref_type'       => 'purchase',
                            'ref_id'         => $purchase->id,
                            'created_by'     => $uid,
                        ]);
                    }
                }
            });
            $purchase->refresh();
            $this->info("✓ Purchase received — stock posted to depot, supplier invoice created");
        } else {
            $this->info("→ Purchase already {$purchase->status}");
        }

        // ═══════════════════════════════════════════════════════════════════
        // 4 — SALE (draft)
        // ═══════════════════════════════════════════════════════════════════
        $this->line('');
        $this->info('── SALE ──────────────────────────────────────────────');

        $saleQty    = 400_000;   // litres
        $salePrice  = 0.960;     // USD/L
        $saleTotal  = round($saleQty * $salePrice, 2);
        $saleFreight = 3_600;    // USD

        $saleRef = 'SO-KIN-' . now()->format('Y') . '-0001';

        $saleId = DB::table('sales')->where('company_id', $cid)->where('reference', $saleRef)->value('id');
        if (!$saleId) {
            $saleId = DB::table('sales')->insertGetId([
                'company_id'      => $cid,
                'depot_id'        => $depotId,
                'product_id'      => $pid,
                'client_id'       => $clientId,
                'client_name'     => 'Glencore DRC',
                'reference'       => $saleRef,
                'sale_date'       => now()->subDays(5)->toDateString(),
                'qty'             => $saleQty,
                'unit_price'      => $salePrice,
                'currency'        => 'USD',
                'total'           => $saleTotal,
                'status'          => 'draft',
                'delivery_mode'   => 'truck',
                'transporter_id'  => $transporterId,
                'truck_no'        => 'KIN-5541-A',
                'trailer_no'      => 'TR-881',
                'driver_name'     => 'Denis Kabila',
                'waybill_no'      => 'WB-2026-0041',
                'freight_amount'  => $saleFreight,
                'freight_currency'=> 'USD',
                'delivery_notes'  => 'Demo sale — 400KL AGO to Glencore DRC',
                'created_by'      => $uid,
                'updated_by'      => $uid,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        $sale = Sale::find($saleId);
        $this->info("Sale {$saleRef} — status: {$sale->status}");

        // ── 4a Post sale ───────────────────────────────────────────────────
        if ($sale->status === 'draft') {
            DB::transaction(function () use ($sale, $ledger, $uid, $saleTotal) {
                $result = $ledger->issue(
                    [
                        'company_id'    => (int) $sale->company_id,
                        'product_id'    => (int) $sale->product_id,
                        'from_depot_id' => (int) $sale->depot_id,
                        'qty'           => (float) $sale->qty,
                        'ref_type'      => 'sale',
                        'ref_id'        => (int) $sale->id,
                        'reference'     => 'sale:' . $sale->id,
                        'notes'         => 'Demo sale issue',
                        'created_by'    => $uid,
                        'updated_by'    => $uid,
                    ],
                    ['type' => 'issue', 'ref_type' => 'sale', 'ref_id' => (int) $sale->id,
                     'from_depot_id' => (int) $sale->depot_id]
                );

                $movement  = $result['movement'] ?? null;
                $cogsTotal = (float) ($result['cogs_total'] ?? 0);

                if (!$movement) {
                    throw new \RuntimeException('Inventory issue failed.');
                }

                $sale->inventory_movement_id = $movement->id;
                $sale->cogs_total   = round($cogsTotal, 2);
                $sale->gross_profit = round((float) $sale->total - $cogsTotal, 2);
                $sale->status       = 'posted';
                $sale->posted_by    = $uid;
                $sale->posted_at    = now()->subDays(5);
                $sale->updated_by   = $uid;
                $sale->save();

                // Freight charge on transporter ledger
                if ($sale->transporter_id && $sale->freight_amount > 0) {
                    $exists = TransporterLedgerEntry::where('ref_type', Sale::class)
                        ->where('ref_id', $sale->id)
                        ->where('type', 'freight_charge')
                        ->exists();
                    if (!$exists) {
                        TransporterLedgerEntry::create([
                            'company_id'     => $sale->company_id,
                            'transporter_id' => $sale->transporter_id,
                            'type'           => 'freight_charge',
                            'sale_id'        => $sale->id,
                            'amount'         => (float) $sale->freight_amount,
                            'currency'       => $sale->freight_currency ?: 'USD',
                            'description'    => "Freight for {$sale->reference}",
                            'entry_date'     => now()->subDays(5)->toDateString(),
                            'ref_type'       => Sale::class,
                            'ref_id'         => $sale->id,
                            'created_by'     => $uid,
                        ]);
                    }
                }
            });
            $sale->refresh();
            $this->info("✓ Sale posted — stock issued, COGS: $" . number_format($sale->cogs_total, 2) . ", GP: $" . number_format($sale->gross_profit, 2));
        } else {
            $this->info("→ Sale already {$sale->status}");
        }

        // ═══════════════════════════════════════════════════════════════════
        // 5 — INVOICE for the sale
        // ═══════════════════════════════════════════════════════════════════
        $this->line('');
        $this->info('── INVOICE ───────────────────────────────────────────');

        $invoiceId = DB::table('invoices')->where('sale_id', $sale->id)->where('type', 'invoice')->value('id');

        if (!$invoiceId) {
            $seq = DB::table('invoices')->where('company_id', $cid)->max('sequence_no') + 1;
            $invoiceNum = 'INV-' . str_pad($seq, 4, '0', STR_PAD_LEFT) . '/' . now()->format('Y');

            $invoiceId = DB::table('invoices')->insertGetId([
                'company_id'    => $cid,
                'client_id'     => $clientId,
                'sale_id'       => $sale->id,
                'invoice_number'=> $invoiceNum,
                'sequence_no'   => $seq,
                'type'          => 'invoice',
                'status'        => 'outstanding',
                'currency'      => 'USD',
                'subtotal'      => $sale->total,
                'tax_rate'      => 0,
                'tax_amount'    => 0,
                'discount_amount'=> 0,
                'total'         => $sale->total,
                'paid_amount'   => 0,
                'issued_date'   => now()->subDays(5)->toDateString(),
                'due_date'      => now()->addDays(25)->toDateString(),
                'payment_terms' => 'Net 30',
                'bank_details'  => 'Rawbank – Kinshasa | USD A/C 00123-456-78 | SWIFT: RAWBCDKIXXX',
                'footer_text'   => 'Thank you for your business.',
                'notes'         => "Sale {$sale->reference} — 400,000L AGO",
                'created_by'    => $uid,
                'updated_by'    => $uid,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $this->info("✓ Invoice created: {$invoiceNum} for $" . number_format($sale->total, 2));
        } else {
            $invoiceNum = DB::table('invoices')->where('id', $invoiceId)->value('invoice_number');
            $this->info("→ Invoice exists: {$invoiceNum}");
        }

        // ── 5a Record payment on invoice ───────────────────────────────────
        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        if ((float) $invoice->paid_amount < (float) $invoice->total) {
            DB::table('invoices')->where('id', $invoiceId)->update([
                'paid_amount' => $invoice->total,
                'status'      => 'paid',
                'paid_at'     => now()->subDays(2),
                'updated_by'  => $uid,
                'updated_at'  => now(),
            ]);
            $this->info("✓ Invoice marked PAID — $" . number_format($invoice->total, 2));
        }

        // ═══════════════════════════════════════════════════════════════════
        // 6 — SUPPLIER PAYMENT
        // ═══════════════════════════════════════════════════════════════════
        $this->line('');
        $this->info('── SUPPLIER PAYMENT ──────────────────────────────────');

        $supplierPaymentExists = DB::table('supplier_ledger_entries')
            ->where('company_id', $cid)
            ->where('supplier_id', $supplierId)
            ->where('type', 'payment')
            ->exists();

        if (!$supplierPaymentExists) {
            DB::table('supplier_ledger_entries')->insert([
                'company_id'  => $cid,
                'supplier_id' => $supplierId,
                'type'        => 'payment',
                'amount'      => -round(500_000 * 0.720, 2),
                'currency'    => 'USD',
                'description' => "Payment for {$ref} — bank transfer",
                'entry_date'  => now()->subDays(3)->toDateString(),
                'ref_type'    => null,
                'ref_id'      => null,
                'created_by'  => $uid,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $this->info("✓ Supplier payment recorded: $" . number_format(500_000 * 0.720, 2));
        } else {
            $this->info("→ Supplier payment already exists");
        }

        // ═══════════════════════════════════════════════════════════════════
        // 7 — TRANSPORTER PAYMENT (settle freight)
        // ═══════════════════════════════════════════════════════════════════
        if ($transporterId) {
            $this->line('');
            $this->info('── TRANSPORTER PAYMENT ───────────────────────────────');

            $tPayExists = TransporterLedgerEntry::where('company_id', $cid)
                ->where('transporter_id', $transporterId)
                ->where('type', 'payment')
                ->exists();

            if (!$tPayExists) {
                TransporterLedgerEntry::create([
                    'company_id'     => $cid,
                    'transporter_id' => $transporterId,
                    'type'           => 'payment',
                    'amount'         => $freightAmt + $saleFreight,
                    'currency'       => 'USD',
                    'description'    => 'Freight payment — purchase + sale batch',
                    'entry_date'     => now()->subDays(1)->toDateString(),
                    'created_by'     => $uid,
                ]);
                $this->info("✓ Transporter payment recorded: $" . number_format($freightAmt + $saleFreight, 2));
            } else {
                $this->info("→ Transporter payment already exists");
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // SUMMARY
        // ═══════════════════════════════════════════════════════════════════
        $this->line('');
        $this->info('════════════════════════════════════════════════════════');
        $this->info('  DEMO BATCH COMPLETE');
        $this->info('════════════════════════════════════════════════════════');

        $sale->refresh();
        $this->table(
            ['Item', 'Value'],
            [
                ['Purchase', "{$ref}  500,000L @ \$0.720 = \$" . number_format(500_000 * 0.720, 0)],
                ['Sale', "{$saleRef}  400,000L @ \$0.960 = \$" . number_format($saleTotal, 0)],
                ['COGS', "\$" . number_format($sale->cogs_total, 2)],
                ['Gross Profit', "\$" . number_format($sale->gross_profit, 2)],
                ['Margin', $sale->total > 0 ? round($sale->gross_profit / $sale->total * 100, 1) . "%" : 'n/a'],
                ['Remaining stock', "100,000L in Kinshasa Terminal"],
                ['Invoice', number_format($invoice->total, 2) . " USD — PAID"],
                ['Supplier', "{$supplier->name} — invoice posted + paid"],
                ['Transporter', $transporter ? "{$transporter->name} — freight posted + paid" : 'n/a'],
            ]
        );

        $this->line('');
        $this->info('You can now browse:');
        $this->line("  → Purchases → {$ref}");
        $this->line("  → Sales     → {$saleRef}");
        $this->line("  → Suppliers → {$supplier->name}");
        $this->line("  → Depot Stock (Kinshasa Terminal)");
        $this->line("  → Dashboard → Financial Snapshot");

        return 0;
    }

    private function wipe(int $cid): void
    {
        $this->warn('Wiping demo data...');
        DB::table('supplier_ledger_entries')->where('company_id', $cid)->delete();
        DB::table('transporter_ledger_entries')->where('company_id', $cid)->delete();
        DB::table('invoices')->where('company_id', $cid)->delete();
        DB::table('sales')->where('company_id', $cid)->delete();
        DB::table('inventory_consumptions')->where('company_id', $cid)->delete();
        DB::table('inventory_movements')->where('company_id', $cid)->delete();
        DB::table('depot_stocks')->where('company_id', $cid)->delete();
        DB::table('inventory_periods')->where('company_id', $cid)->delete();
        DB::table('batches')->where('company_id', $cid)->delete();
        DB::table('purchases')->where('company_id', $cid)->delete();
        DB::table('clients')->where('company_id', $cid)->delete();
        DB::table('depots')->where('company_id', $cid)->where('is_system', false)->delete();
        // Also wipe system depot so cross dock starts clean
        DB::table('depots')->where('company_id', $cid)->where('is_system', true)->delete();
        $this->warn('→ Wiped.');
    }
}
