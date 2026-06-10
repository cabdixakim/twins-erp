<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    private int $companyId = 1;
    private array $productIds = [];
    private array $depotIds   = [];
    private array $supplierIds= [];
    private array $transporterIds = [];
    private array $clientIds  = [];
    private int   $periodId   = 0;

    public function run(): void
    {
        $co = DB::table('companies')->where('id', $this->companyId)->first();
        if (!$co) {
            $this->command->error('Company id=1 not found. Run the app and create a company first.');
            return;
        }

        $this->command->info('Seeding dummy data for: ' . $co->name);

        DB::transaction(function () {
            $this->seedUsers();
            $this->seedProducts();
            $this->seedDepots();
            $this->seedSuppliers();
            $this->seedTransporters();
            $this->seedClients();
            $this->seedInventoryPeriod();
            $this->seedLocalDepotPurchases();
            $this->seedCrossDockPurchases();
            $this->seedImportPurchases();
            $this->seedSales();
            $this->seedPettyCash();
        });

        $this->command->info('Done! All dummy data seeded.');
    }

    // ─── Users ────────────────────────────────────────────────────────────────

    private function seedUsers(): void
    {
        $managerRole = DB::table('roles')->where('slug', 'manager')->value('id');
        $accountantRole = DB::table('roles')->where('slug', 'accountant')->value('id');
        $transportRole  = DB::table('roles')->where('slug', 'transport-controller')->value('id');

        $users = [
            ['name' => 'Alice Mwangi',  'email' => 'alice@twins.com',     'role_id' => $managerRole],
            ['name' => 'Brian Otieno',  'email' => 'brian@twins.com',     'role_id' => $accountantRole],
            ['name' => 'Carol Nakato',  'email' => 'carol@twins.com',     'role_id' => $transportRole],
        ];

        foreach ($users as $u) {
            if (DB::table('users')->where('email', $u['email'])->exists()) continue;
            $uid = DB::table('users')->insertGetId([
                'name'              => $u['name'],
                'email'             => $u['email'],
                'password'          => Hash::make('Password1!'),
                'active_company_id' => $this->companyId,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            DB::table('company_user')->insertOrIgnore([
                'company_id' => $this->companyId,
                'user_id'    => $uid,
            ]);
        }
        $this->command->info('Users: done');
    }

    // ─── Products ─────────────────────────────────────────────────────────────

    private function seedProducts(): void
    {
        $products = [
            ['name' => 'AGO',    'code' => 'AGO',  'category' => 'diesel',    'allowed_loss_pct' => 0.30, 'default_density' => 0.845],
            ['name' => 'PMS',    'code' => 'PMS',  'category' => 'petrol',    'allowed_loss_pct' => 0.50, 'default_density' => 0.745],
            ['name' => 'Jet A1', 'code' => 'JET',  'category' => 'jet_fuel',  'allowed_loss_pct' => 0.20, 'default_density' => 0.800],
            ['name' => 'HFO',    'code' => 'HFO',  'category' => 'heavy_fuel','allowed_loss_pct' => 0.40, 'default_density' => 0.960],
        ];

        foreach ($products as $p) {
            $existing = DB::table('products')
                ->where('company_id', $this->companyId)
                ->where('code', $p['code'])
                ->value('id');

            if ($existing) {
                $this->productIds[$p['code']] = $existing;
                continue;
            }

            $id = DB::table('products')->insertGetId(array_merge($p, [
                'company_id'  => $this->companyId,
                'base_uom'    => 'litres',
                'is_active'   => true,
                'created_by'  => 1,
                'updated_by'  => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]));
            $this->productIds[$p['code']] = $id;
        }
        $this->command->info('Products: done');
    }

    // ─── Depots ───────────────────────────────────────────────────────────────

    private function seedDepots(): void
    {
        $depots = [
            ['name' => 'Kinshasa Main Terminal',     'city' => 'Kinshasa',  'storage_fee_per_1000_l' => 1500, 'default_currency' => 'CDF'],
            ['name' => 'Lubumbashi Depot',           'city' => 'Lubumbashi','storage_fee_per_1000_l' => 1800, 'default_currency' => 'CDF'],
            ['name' => 'Matadi Port Terminal',       'city' => 'Matadi',    'storage_fee_per_1000_l' => 2000, 'default_currency' => 'USD'],
            ['name' => 'Kolwezi Industrial Depot',   'city' => 'Kolwezi',   'storage_fee_per_1000_l' => 2200, 'default_currency' => 'USD'],
            ['name' => 'Goma Northern Terminal',     'city' => 'Goma',      'storage_fee_per_1000_l' => 2500, 'default_currency' => 'USD'],
        ];

        foreach ($depots as $d) {
            $existing = DB::table('depots')
                ->where('company_id', $this->companyId)
                ->where('name', $d['name'])
                ->value('id');

            if ($existing) {
                $this->depotIds[] = $existing;
                continue;
            }

            $id = DB::table('depots')->insertGetId(array_merge($d, [
                'company_id'            => $this->companyId,
                'default_shrinkage_pct' => 0.10,
                'is_active'             => true,
                'is_system'             => false,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]));
            $this->depotIds[] = $id;
        }
        $this->command->info('Depots: done');
    }

    // ─── Suppliers ────────────────────────────────────────────────────────────

    private function seedSuppliers(): void
    {
        $suppliers = [
            ['name' => 'Vitol Energy SA',         'type' => 'trader',     'country' => 'Switzerland', 'city' => 'Geneva',    'default_currency' => 'USD'],
            ['name' => 'Trafigura Trading LLC',   'type' => 'trader',     'country' => 'Singapore',   'city' => 'Singapore', 'default_currency' => 'USD'],
            ['name' => 'CNOOC Trading Ltd',       'type' => 'national',   'country' => 'China',       'city' => 'Beijing',   'default_currency' => 'USD'],
            ['name' => 'TotalEnergies DRC',       'type' => 'national',   'country' => 'DRC',         'city' => 'Kinshasa',  'default_currency' => 'CDF'],
        ];

        foreach ($suppliers as $s) {
            $existing = DB::table('suppliers')
                ->where('company_id', $this->companyId)
                ->where('name', $s['name'])
                ->value('id');

            if ($existing) {
                $this->supplierIds[] = $existing;
                continue;
            }

            $id = DB::table('suppliers')->insertGetId(array_merge($s, [
                'company_id'     => $this->companyId,
                'contact_person' => 'Trade Desk',
                'phone'          => '+1 212 555 ' . rand(1000, 9999),
                'email'          => strtolower(str_replace(' ', '.', $s['name'])) . '@example.com',
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]));
            $this->supplierIds[] = $id;
        }
        $this->command->info('Suppliers: done');
    }

    // ─── Transporters ─────────────────────────────────────────────────────────

    private function seedTransporters(): void
    {
        $transporters = [
            ['name' => 'Bolloré Transport DRC',    'type' => 'road',      'country' => 'DRC',   'default_rate_per_1000_l' => 45000],
            ['name' => 'Congo Logistics SARL',     'type' => 'road',      'country' => 'DRC',   'default_rate_per_1000_l' => 42000],
            ['name' => 'Kasai Freight Ltd',        'type' => 'road',      'country' => 'DRC',   'default_rate_per_1000_l' => 48000],
            ['name' => 'Matadi Shipping Co',       'type' => 'maritime',  'country' => 'DRC',   'default_rate_per_1000_l' => 18000],
        ];

        foreach ($transporters as $t) {
            $existing = DB::table('transporters')
                ->where('company_id', $this->companyId)
                ->where('name', $t['name'])
                ->value('id');

            if ($existing) {
                $this->transporterIds[] = $existing;
                continue;
            }

            $id = DB::table('transporters')->insertGetId(array_merge($t, [
                'company_id'     => $this->companyId,
                'city'           => 'Kinshasa',
                'contact_person' => 'Operations Manager',
                'phone'          => '+243 81 ' . rand(1000000, 9999999),
                'email'          => strtolower(str_replace(' ', '', $t['name'])) . '@example.com',
                'default_currency'=> 'CDF',
                'payment_terms'  => 30,
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]));
            $this->transporterIds[] = $id;
        }
        $this->command->info('Transporters: done');
    }

    // ─── Clients ──────────────────────────────────────────────────────────────

    private function seedClients(): void
    {
        $clients = [
            ['name' => 'Gécamines Mining',        'code' => 'GEC', 'type' => 'mining',        'city' => 'Lubumbashi', 'credit_limit' => 500000],
            ['name' => 'Ivanhoe Mines DRC',       'code' => 'IVN', 'type' => 'mining',        'city' => 'Kolwezi',    'credit_limit' => 750000],
            ['name' => 'Congo Breweries Ltd',     'code' => 'CBL', 'type' => 'industrial',    'city' => 'Kinshasa',   'credit_limit' => 200000],
            ['name' => 'ONATRA Public Corp',      'code' => 'ONA', 'type' => 'government',    'city' => 'Kinshasa',   'credit_limit' => 1000000],
            ['name' => 'Air Congo SAC',           'code' => 'AIC', 'type' => 'aviation',      'city' => 'Kinshasa',   'credit_limit' => 300000],
            ['name' => 'Banro Gold Mining',       'code' => 'BAN', 'type' => 'mining',        'city' => 'Bukavu',     'credit_limit' => 400000],
            ['name' => 'MTN Congo',               'code' => 'MTN', 'type' => 'telecoms',      'city' => 'Kinshasa',   'credit_limit' => 150000],
            ['name' => 'SNH Petroleum',           'code' => 'SNH', 'type' => 'trader',        'city' => 'Kinshasa',   'credit_limit' => 600000],
        ];

        foreach ($clients as $c) {
            $existing = DB::table('clients')
                ->where('company_id', $this->companyId)
                ->where('code', $c['code'])
                ->value('id');

            if ($existing) {
                $this->clientIds[] = $existing;
                continue;
            }

            $id = DB::table('clients')->insertGetId(array_merge($c, [
                'company_id'     => $this->companyId,
                'country'        => 'DRC',
                'contact_person' => 'Procurement Dept',
                'phone'          => '+243 97 ' . rand(1000000, 9999999),
                'email'          => strtolower($c['code']) . '@example.com',
                'currency'       => 'USD',
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]));
            $this->clientIds[] = $id;
        }
        $this->command->info('Clients: done');
    }

    // ─── Inventory Period ─────────────────────────────────────────────────────

    private function seedInventoryPeriod(): void
    {
        $existing = DB::table('inventory_periods')
            ->where('company_id', $this->companyId)
            ->where('status', 'open')
            ->value('id');

        if ($existing) {
            $this->periodId = $existing;
            $this->command->info('Inventory period: already exists');
            return;
        }

        $this->periodId = DB::table('inventory_periods')->insertGetId([
            'company_id'     => $this->companyId,
            'name'           => 'Period 1 — Jan 2026',
            'costing_method' => 'weighted_average',
            'starts_at'      => '2026-01-01',
            'ends_at'        => '2026-12-31',
            'status'         => 'open',
            'created_by'     => 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
        $this->command->info('Inventory period: created');
    }

    // ─── Local Depot Purchases ────────────────────────────────────────────────

    private function seedLocalDepotPurchases(): void
    {
        $scenarios = [
            // [product_code, supplier_idx, depot_idx, qty, unit_price, days_ago]
            ['AGO', 0, 0, 500000, 0.72, 90],
            ['PMS', 1, 1, 300000, 0.68, 85],
            ['AGO', 2, 2, 800000, 0.73, 80],
            ['Jet A1', 3, 0, 200000, 0.95, 75],
            ['HFO',  0, 3, 1000000,0.45, 70],
            ['AGO',  1, 4, 600000, 0.71, 65],
            ['PMS',  2, 0, 400000, 0.67, 60],
            ['AGO',  3, 1, 750000, 0.74, 55],
            ['Jet A1',0, 2, 250000,0.96, 50],
            ['PMS',  1, 3, 350000, 0.69, 45],
            ['HFO',  2, 4, 900000, 0.44, 40],
            ['AGO',  3, 0, 550000, 0.75, 35],
        ];

        foreach ($scenarios as $i => $s) {
            [$prodCode, $supIdx, $depIdx, $qty, $price, $daysAgo] = $s;
            $productId  = $this->productIds[$prodCode] ?? reset($this->productIds);
            $supplierId = $this->supplierIds[$supIdx % count($this->supplierIds)];
            $depotId    = $this->depotIds[$depIdx % count($this->depotIds)];
            $date       = Carbon::now()->subDays($daysAgo)->toDateString();
            $seq        = $i + 1;
            $ref        = 'PO-TWN-2026-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            // Skip if already exists
            if (DB::table('purchases')->where('reference', $ref)->exists()) continue;

            // Batch
            $batchId = DB::table('batches')->insertGetId([
                'company_id'   => $this->companyId,
                'product_id'   => $productId,
                'code'         => 'B-' . $ref,
                'name'         => $ref . ' Batch',
                'source_type'  => 'purchase',
                'source_ref'   => $ref,
                'supplier_id'  => $supplierId,
                'qty_purchased'=> $qty,
                'qty_received' => $qty,
                'qty_remaining'=> $qty,
                'total_cost'   => round($qty * $price, 2),
                'unit_cost'    => $price,
                'status'       => 'active',
                'purchased_at' => $date,
                'created_by'   => 1,
                'updated_by'   => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Purchase
            DB::table('purchases')->insertGetId([
                'company_id'  => $this->companyId,
                'type'        => 'local_depot',
                'supplier_id' => $supplierId,
                'product_id'  => $productId,
                'batch_id'    => $batchId,
                'depot_id'    => $depotId,
                'purchase_date'=> $date,
                'qty'         => $qty,
                'unit_price'  => $price,
                'currency'    => 'USD',
                'status'      => 'received',
                'reference'   => $ref,
                'sequence_no' => $seq,
                'created_by'  => 1,
                'updated_by'  => 1,
                'actioned_at' => now()->subDays($daysAgo - 1),
                'actioned_by' => 1,
                'created_at'  => now()->subDays($daysAgo),
                'updated_at'  => now()->subDays($daysAgo - 1),
            ]);

            // Inventory movement (receipt)
            $movId = DB::table('inventory_movements')->insertGetId([
                'company_id'  => $this->companyId,
                'product_id'  => $productId,
                'type'        => 'receipt',
                'ref_type'    => 'purchase',
                'ref_id'      => DB::table('purchases')->where('reference', $ref)->value('id'),
                'reference'   => $ref,
                'batch_id'    => $batchId,
                'to_depot_id' => $depotId,
                'qty'         => $qty,
                'unit_cost'   => $price,
                'total_cost'  => round($qty * $price, 2),
                'period_id'   => $this->periodId,
                'notes'       => "Receipt for $ref",
                'created_by'  => 1,
                'created_at'  => now()->subDays($daysAgo - 1),
                'updated_at'  => now()->subDays($daysAgo - 1),
            ]);

            // Depot stock (upsert by adding to existing if same product+depot+batch)
            $stockKey = ['company_id' => $this->companyId, 'depot_id' => $depotId, 'product_id' => $productId, 'batch_id' => $batchId];
            $existing = DB::table('depot_stocks')->where($stockKey)->first();
            if ($existing) {
                DB::table('depot_stocks')->where($stockKey)->update([
                    'qty_on_hand' => $existing->qty_on_hand + $qty,
                    'unit_cost'   => $price,
                    'updated_by'  => 1,
                    'updated_at'  => now(),
                ]);
            } else {
                DB::table('depot_stocks')->insert(array_merge($stockKey, [
                    'qty_on_hand' => $qty,
                    'qty_reserved'=> 0,
                    'unit_cost'   => $price,
                    'created_by'  => 1,
                    'updated_by'  => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]));
            }

            // Supplier ledger — invoice
            DB::table('supplier_ledger_entries')->insert([
                'company_id'  => $this->companyId,
                'supplier_id' => $supplierId,
                'type'        => 'purchase_invoice',
                'amount'      => round($qty * $price, 2),
                'currency'    => 'USD',
                'description' => "Invoice for $ref",
                'entry_date'  => $date,
                'ref_type'    => 'purchase',
                'ref_id'      => DB::table('purchases')->where('reference', $ref)->value('id'),
                'created_by'  => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Batch costs (freight + duty)
            DB::table('batch_costs')->insert([
                [
                    'company_id'          => $this->companyId,
                    'batch_id'            => $batchId,
                    'purchase_id'         => DB::table('purchases')->where('reference', $ref)->value('id'),
                    'category'            => 'freight',
                    'description'         => 'Road freight to depot',
                    'amount'              => round($qty * 0.008, 2),
                    'currency'            => 'USD',
                    'exchange_rate'       => 1.00,
                    'amount_base'         => round($qty * 0.008, 2),
                    'is_included_in_cost' => true,
                    'entry_date'          => $date,
                    'auto_posted'         => false,
                    'created_by'          => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ],
                [
                    'company_id'          => $this->companyId,
                    'batch_id'            => $batchId,
                    'purchase_id'         => DB::table('purchases')->where('reference', $ref)->value('id'),
                    'category'            => 'duty',
                    'description'         => 'Import duty / taxes',
                    'amount'              => round($qty * 0.015, 2),
                    'currency'            => 'USD',
                    'exchange_rate'       => 1.00,
                    'amount_base'         => round($qty * 0.015, 2),
                    'is_included_in_cost' => true,
                    'entry_date'          => $date,
                    'auto_posted'         => false,
                    'created_by'          => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ],
            ]);

            // Partial supplier payment (60% paid)
            $paid = round($qty * $price * 0.6, 2);
            DB::table('supplier_ledger_entries')->insert([
                'company_id'  => $this->companyId,
                'supplier_id' => $supplierId,
                'type'        => 'payment',
                'amount'      => -$paid,
                'currency'    => 'USD',
                'description' => "Partial payment for $ref",
                'entry_date'  => Carbon::now()->subDays($daysAgo - 5)->toDateString(),
                'ref_type'    => 'purchase',
                'ref_id'      => DB::table('purchases')->where('reference', $ref)->value('id'),
                'created_by'  => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $this->command->info('Local depot purchases: done');
    }

    // ─── Cross-Dock Purchases ─────────────────────────────────────────────────

    private function seedCrossDockPurchases(): void
    {
        $crossDockDepotId = DB::table('depots')
            ->where('company_id', $this->companyId)
            ->where('is_system', true)
            ->value('id');

        if (!$crossDockDepotId) {
            $crossDockDepotId = DB::table('depots')->insertGetId([
                'company_id'   => $this->companyId,
                'name'         => 'CROSS DOCK',
                'city'         => 'Virtual',
                'is_system'    => true,
                'is_active'    => true,
                'default_currency' => 'USD',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $scenarios = [
            ['AGO', 0, 0, 300000, 0.70, 30, 'dispatched'],
            ['PMS', 1, 1, 150000, 0.66, 28, 'dispatched'],
            ['AGO', 2, 2, 400000, 0.72, 25, 'transferred'],
            ['Jet A1',3,3, 100000, 0.94, 22, 'transferred'],
            ['AGO', 0, 4, 250000, 0.71, 20, 'dispatched'],
        ];

        $seq = 100;
        foreach ($scenarios as $i => [$prodCode, $supIdx, $clientIdx, $qty, $price, $daysAgo, $status]) {
            $seq++;
            $productId  = $this->productIds[$prodCode] ?? reset($this->productIds);
            $supplierId = $this->supplierIds[$supIdx % count($this->supplierIds)];
            $clientId   = $this->clientIds[$clientIdx % count($this->clientIds)] ?? null;
            $depotId    = $this->depotIds[($i + 1) % count($this->depotIds)];
            $date       = Carbon::now()->subDays($daysAgo)->toDateString();
            $ref        = 'XD-TWN-2026-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);

            if (DB::table('purchases')->where('reference', $ref)->exists()) continue;

            $batchId = DB::table('batches')->insertGetId([
                'company_id'   => $this->companyId,
                'product_id'   => $productId,
                'code'         => 'B-' . $ref,
                'name'         => $ref . ' Batch',
                'source_type'  => 'purchase',
                'source_ref'   => $ref,
                'supplier_id'  => $supplierId,
                'qty_purchased'=> $qty,
                'qty_received' => $qty,
                'qty_remaining'=> $status === 'dispatched' ? 0 : $qty,
                'total_cost'   => round($qty * $price, 2),
                'unit_cost'    => $price,
                'status'       => $status === 'dispatched' ? 'consumed' : 'active',
                'purchased_at' => $date,
                'created_by'   => 1,
                'updated_by'   => 1,
                'created_at'   => now()->subDays($daysAgo),
                'updated_at'   => now(),
            ]);

            DB::table('purchases')->insert([
                'company_id'  => $this->companyId,
                'type'        => 'cross_dock',
                'supplier_id' => $supplierId,
                'product_id'  => $productId,
                'batch_id'    => $batchId,
                'depot_id'    => $status === 'transferred' ? $depotId : $crossDockDepotId,
                'purchase_date'=> $date,
                'qty'         => $qty,
                'unit_price'  => $price,
                'currency'    => 'USD',
                'status'      => $status,
                'reference'   => $ref,
                'sequence_no' => $seq,
                'client_id'   => $status === 'dispatched' ? $clientId : null,
                'created_by'  => 1,
                'updated_by'  => 1,
                'actioned_at' => now()->subDays($daysAgo - 2),
                'actioned_by' => 1,
                'created_at'  => now()->subDays($daysAgo),
                'updated_at'  => now()->subDays($daysAgo - 2),
            ]);

            // Supplier ledger invoice
            DB::table('supplier_ledger_entries')->insert([
                'company_id'  => $this->companyId,
                'supplier_id' => $supplierId,
                'type'        => 'purchase_invoice',
                'amount'      => round($qty * $price, 2),
                'currency'    => 'USD',
                'description' => "Invoice for $ref",
                'entry_date'  => $date,
                'ref_type'    => 'purchase',
                'ref_id'      => DB::table('purchases')->where('reference', $ref)->value('id'),
                'created_by'  => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $this->command->info('Cross-dock purchases: done');
    }

    // ─── Import Purchases ─────────────────────────────────────────────────────

    private function seedImportPurchases(): void
    {
        $scenarios = [
            ['AGO', 0, 2, 2000000, 0.69, 120],
            ['PMS', 1, 3, 1500000, 0.65, 100],
            ['AGO', 2, 4, 2500000, 0.70, 80],
        ];

        $seq = 200;
        foreach ($scenarios as $i => [$prodCode, $supIdx, $depIdx, $qty, $price, $daysAgo]) {
            $seq++;
            $productId  = $this->productIds[$prodCode] ?? reset($this->productIds);
            $supplierId = $this->supplierIds[$supIdx % count($this->supplierIds)];
            $depotId    = $this->depotIds[$depIdx % count($this->depotIds)];
            $date       = Carbon::now()->subDays($daysAgo)->toDateString();
            $ref        = 'IMP-TWN-2026-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);

            if (DB::table('purchases')->where('reference', $ref)->exists()) continue;

            $batchId = DB::table('batches')->insertGetId([
                'company_id'   => $this->companyId,
                'product_id'   => $productId,
                'code'         => 'B-' . $ref,
                'name'         => $ref . ' Batch',
                'source_type'  => 'purchase',
                'source_ref'   => $ref,
                'supplier_id'  => $supplierId,
                'qty_purchased'=> $qty,
                'qty_received' => $qty,
                'qty_remaining'=> $qty,
                'total_cost'   => round($qty * $price, 2),
                'unit_cost'    => $price,
                'status'       => 'active',
                'purchased_at' => $date,
                'created_by'   => 1,
                'updated_by'   => 1,
                'created_at'   => now()->subDays($daysAgo),
                'updated_at'   => now(),
            ]);

            $purchaseId = DB::table('purchases')->insertGetId([
                'company_id'   => $this->companyId,
                'type'         => 'import',
                'supplier_id'  => $supplierId,
                'product_id'   => $productId,
                'batch_id'     => $batchId,
                'depot_id'     => $depotId,
                'purchase_date'=> $date,
                'qty'          => $qty,
                'qty_delivered'=> $qty,
                'unit_price'   => $price,
                'currency'     => 'USD',
                'status'       => 'received',
                'reference'    => $ref,
                'sequence_no'  => $seq,
                'vessel_name'  => 'MV Ocean Carrier ' . ($i + 1),
                'voyage_no'    => 'VOY-2026-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'loading_port' => ['Rotterdam', 'Fujairah', 'Durban'][$i % 3],
                'discharge_port'=> 'Matadi',
                'bl_number'    => 'BL-2026-' . rand(10000, 99999),
                'bl_date'      => Carbon::now()->subDays($daysAgo + 10)->toDateString(),
                'eta_date'     => Carbon::now()->subDays($daysAgo - 5)->toDateString(),
                'created_by'   => 1,
                'updated_by'   => 1,
                'actioned_at'  => now()->subDays($daysAgo - 3),
                'actioned_by'  => 1,
                'created_at'   => now()->subDays($daysAgo),
                'updated_at'   => now()->subDays($daysAgo - 3),
            ]);

            // Nomination
            $transporterId = $this->transporterIds[3 % count($this->transporterIds)]; // maritime
            $nominationId = DB::table('import_nominations')->insertGetId([
                'company_id'         => $this->companyId,
                'purchase_id'        => $purchaseId,
                'transporter_id'     => $transporterId,
                'rate_per_1000l'     => 18000,
                'allowed_loss_pct'   => 0.30,
                'short_charge_rate'  => 500,
                'short_charge_currency' => 'USD',
                'advances'           => round($qty * $price * 0.10, 2),
                'advances_currency'  => 'USD',
                'currency'           => 'USD',
                'status'             => 'active',
                'created_by'         => 1,
                'created_at'         => now()->subDays($daysAgo),
                'updated_at'         => now(),
            ]);

            // Trucks (3 per import)
            $truckCapacity = intdiv($qty, 3);
            $truckPlates   = ['CD 123 KIN', 'CD 456 LUB', 'CD 789 MAT'];
            foreach ($truckPlates as $j => $plate) {
                $loaded    = $truckCapacity + ($j === 0 ? $qty % 3 : 0);
                $delivered = intval($loaded * 0.997); // 0.3% loss
                DB::table('import_trucks')->insert([
                    'company_id'       => $this->companyId,
                    'nomination_id'    => $nominationId,
                    'truck_reg'        => $plate,
                    'trailer_reg'      => 'TR-' . ($j + 1) . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'driver_name'      => ['Jean Kabila', 'Pierre Mutombo', 'Marc Ndongo'][$j],
                    'driver_phone'     => '+243 81 ' . rand(1000000, 9999999),
                    'capacity'         => $loaded,
                    'qty_loaded'       => $loaded,
                    'qty_delivered'    => $delivered,
                    'status'           => 'delivered',
                    'in_transit_at'    => Carbon::now()->subDays($daysAgo - 4),
                    'border_cleared_at'=> Carbon::now()->subDays($daysAgo - 6),
                    'border_date'      => Carbon::now()->subDays($daysAgo - 6)->toDateString(),
                    'depot_id'         => $depotId,
                    'pickup_date'      => Carbon::now()->subDays($daysAgo - 2)->toDateString(),
                    'delivery_date'    => Carbon::now()->subDays($daysAgo - 8)->toDateString(),
                    'tr8_number'       => 'TR8-' . rand(10000, 99999),
                    't1_number'        => 'T1-' . rand(10000, 99999),
                    'shortfall_qty'    => $loaded - $delivered,
                    'allowed_loss_qty' => round($loaded * 0.003, 0),
                    'excess_loss_qty'  => 0,
                    'shortfall_charge' => 0,
                    'created_by'       => 1,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            // Inventory movement
            DB::table('inventory_movements')->insert([
                'company_id'  => $this->companyId,
                'product_id'  => $productId,
                'type'        => 'receipt',
                'ref_type'    => 'purchase',
                'ref_id'      => $purchaseId,
                'reference'   => $ref,
                'batch_id'    => $batchId,
                'to_depot_id' => $depotId,
                'qty'         => $qty,
                'unit_cost'   => $price,
                'total_cost'  => round($qty * $price, 2),
                'period_id'   => $this->periodId,
                'notes'       => "Import receipt for $ref",
                'created_by'  => 1,
                'created_at'  => now()->subDays($daysAgo - 3),
                'updated_at'  => now()->subDays($daysAgo - 3),
            ]);

            // Depot stock
            $stockKey = ['company_id' => $this->companyId, 'depot_id' => $depotId, 'product_id' => $productId, 'batch_id' => $batchId];
            $existing = DB::table('depot_stocks')->where($stockKey)->first();
            if ($existing) {
                DB::table('depot_stocks')->where($stockKey)->update([
                    'qty_on_hand' => $existing->qty_on_hand + $qty,
                    'unit_cost'   => $price,
                    'updated_by'  => 1,
                    'updated_at'  => now(),
                ]);
            } else {
                DB::table('depot_stocks')->insert(array_merge($stockKey, [
                    'qty_on_hand' => $qty,
                    'qty_reserved'=> 0,
                    'unit_cost'   => $price,
                    'created_by'  => 1,
                    'updated_by'  => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]));
            }

            // Supplier ledger invoice (proportional per truck qty)
            DB::table('supplier_ledger_entries')->insert([
                'company_id'  => $this->companyId,
                'supplier_id' => $supplierId,
                'type'        => 'purchase_invoice',
                'amount'      => round($qty * $price, 2),
                'currency'    => 'USD',
                'description' => "Import invoice $ref",
                'entry_date'  => $date,
                'ref_type'    => 'purchase',
                'ref_id'      => $purchaseId,
                'created_by'  => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Transporter ledger entries (freight + advance)
            DB::table('transporter_ledger_entries')->insert([
                [
                    'company_id'     => $this->companyId,
                    'transporter_id' => $transporterId,
                    'type'           => 'freight_charge',
                    'ref_type'       => 'purchase',
                    'ref_id'         => $purchaseId,
                    'amount'         => round($qty / 1000 * 18000, 2),
                    'currency'       => 'CDF',
                    'description'    => "Freight for $ref",
                    'entry_date'     => $date,
                    'created_by'     => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ],
                [
                    'company_id'     => $this->companyId,
                    'transporter_id' => $transporterId,
                    'type'           => 'advance',
                    'ref_type'       => 'purchase',
                    'ref_id'         => $purchaseId,
                    'amount'         => -round($qty / 1000 * 18000 * 0.30, 2),
                    'currency'       => 'CDF',
                    'description'    => "Advance payment for $ref",
                    'entry_date'     => $date,
                    'created_by'     => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ],
            ]);

            // Batch costs (freight + border)
            DB::table('batch_costs')->insert([
                [
                    'company_id'          => $this->companyId,
                    'batch_id'            => $batchId,
                    'purchase_id'         => $purchaseId,
                    'category'            => 'freight',
                    'description'         => 'Maritime freight — ' . ['Rotterdam', 'Fujairah', 'Durban'][$i % 3] . ' → Matadi',
                    'amount'              => round($qty * 0.012, 2),
                    'currency'            => 'USD',
                    'exchange_rate'       => 1.00,
                    'amount_base'         => round($qty * 0.012, 2),
                    'is_included_in_cost' => true,
                    'entry_date'          => $date,
                    'auto_posted'         => false,
                    'created_by'          => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ],
                [
                    'company_id'          => $this->companyId,
                    'batch_id'            => $batchId,
                    'purchase_id'         => $purchaseId,
                    'category'            => 'border_charge',
                    'description'         => 'DRC border clearance & T1 fees',
                    'amount'              => round($qty * 0.005, 2),
                    'currency'            => 'USD',
                    'exchange_rate'       => 1.00,
                    'amount_base'         => round($qty * 0.005, 2),
                    'is_included_in_cost' => true,
                    'entry_date'          => $date,
                    'auto_posted'         => false,
                    'created_by'          => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ],
            ]);
        }

        $this->command->info('Import purchases: done');
    }

    // ─── Sales ────────────────────────────────────────────────────────────────

    private function seedSales(): void
    {
        $saleSets = [
            ['AGO', 0, 0, 0, 80000,  0.85, 25],
            ['PMS', 1, 1, 1, 50000,  0.80, 24],
            ['AGO', 2, 2, 2, 120000, 0.87, 23],
            ['Jet A1',0,3,3, 30000,  1.10, 22],
            ['AGO', 1, 4, 4, 90000,  0.86, 21],
            ['PMS', 2, 0, 5, 60000,  0.81, 20],
            ['AGO', 3, 1, 6, 100000, 0.88, 19],
            ['HFO', 0, 2, 7, 200000, 0.55, 18],
            ['AGO', 1, 3, 0, 70000,  0.85, 17],
            ['PMS', 2, 4, 1, 45000,  0.80, 16],
            ['AGO', 3, 0, 2, 130000, 0.87, 15],
            ['Jet A1',0,1,3, 25000,  1.12, 14],
            ['AGO', 1, 2, 4, 85000,  0.86, 13],
            ['PMS', 2, 3, 5, 55000,  0.81, 12],
            ['AGO', 3, 4, 6, 95000,  0.88, 11],
            ['HFO', 0, 0, 7, 180000, 0.55, 10],
            ['AGO', 1, 1, 0, 110000, 0.87, 9],
            ['PMS', 2, 2, 1, 65000,  0.82, 8],
            ['AGO', 3, 3, 2, 140000, 0.89, 7],
            ['Jet A1',0,4,3, 35000,  1.13, 6],
        ];

        foreach ($saleSets as $i => [$prodCode, $transportIdx, $depotIdx, $clientIdx, $qty, $price, $daysAgo]) {
            $productId     = $this->productIds[$prodCode] ?? reset($this->productIds);
            $transporterId = $this->transporterIds[$transportIdx % count($this->transporterIds)];
            $depotId       = $this->depotIds[$depotIdx % count($this->depotIds)];
            $clientId      = $this->clientIds[$clientIdx % count($this->clientIds)];
            $clientName    = DB::table('clients')->where('id', $clientId)->value('name');
            $date          = Carbon::now()->subDays($daysAgo)->toDateString();
            $seq           = $i + 1;
            $ref           = 'SO-TWN-2026-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            if (DB::table('sales')->where('reference', $ref)->exists()) continue;

            $cogs        = round($qty * 0.71, 2); // rough cost approx
            $total       = round($qty * $price, 2);
            $grossProfit = round($total - $cogs, 2);

            $saleId = DB::table('sales')->insertGetId([
                'company_id'     => $this->companyId,
                'depot_id'       => $depotId,
                'product_id'     => $productId,
                'client_id'      => $clientId,
                'client_name'    => $clientName,
                'sequence_no'    => $seq,
                'reference'      => $ref,
                'sale_date'      => $date,
                'qty'            => $qty,
                'unit_price'     => $price,
                'currency'       => 'USD',
                'total'          => $total,
                'cogs_total'     => $cogs,
                'gross_profit'   => $grossProfit,
                'status'         => 'posted',
                'delivery_mode'  => 'truck',
                'transporter_id' => $transporterId,
                'truck_no'       => 'CD ' . rand(100, 999) . ' ' . ['KIN','LUB','MAT'][rand(0,2)],
                'trailer_no'     => 'TR-' . rand(1000, 9999),
                'waybill_no'     => 'WB-2026-' . rand(10000, 99999),
                'driver_name'    => ['Jean Mukendi','Pierre Kasongo','Marc Ilunga','Alice Kabila'][rand(0,3)],
                'qty_delivered'  => intval($qty * 0.998),
                'posted_by'      => 1,
                'posted_at'      => Carbon::now()->subDays($daysAgo - 1),
                'created_by'     => 1,
                'updated_by'     => 1,
                'created_at'     => Carbon::now()->subDays($daysAgo),
                'updated_at'     => Carbon::now()->subDays($daysAgo - 1),
            ]);

            // Inventory movement (issue)
            DB::table('inventory_movements')->insert([
                'company_id'    => $this->companyId,
                'product_id'    => $productId,
                'type'          => 'issue',
                'ref_type'      => 'sale',
                'ref_id'        => $saleId,
                'reference'     => $ref,
                'from_depot_id' => $depotId,
                'qty'           => $qty,
                'unit_cost'     => 0.71,
                'total_cost'    => $cogs,
                'period_id'     => $this->periodId,
                'notes'         => "Sale issue for $ref",
                'created_by'    => 1,
                'created_at'    => Carbon::now()->subDays($daysAgo - 1),
                'updated_at'    => Carbon::now()->subDays($daysAgo - 1),
            ]);

            // Transporter ledger — freight for this delivery
            $freightAmt = round($qty / 1000 * $this->transporterRate($transporterId), 2);
            DB::table('transporter_ledger_entries')->insert([
                'company_id'     => $this->companyId,
                'transporter_id' => $transporterId,
                'type'           => 'freight_charge',
                'ref_type'       => 'sale',
                'ref_id'         => $saleId,
                'sale_id'        => $saleId,
                'amount'         => $freightAmt,
                'currency'       => 'CDF',
                'description'    => "Freight for $ref",
                'entry_date'     => $date,
                'created_by'     => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        $this->command->info('Sales: done');
    }

    private function transporterRate(int $transporterId): int
    {
        return DB::table('transporters')->where('id', $transporterId)->value('default_rate_per_1000_l') ?? 45000;
    }

    // ─── Petty Cash ───────────────────────────────────────────────────────────

    private function seedPettyCash(): void
    {
        $existing = DB::table('petty_cash_accounts')
            ->where('company_id', $this->companyId)
            ->value('id');

        if (!$existing) {
            $accountId = DB::table('petty_cash_accounts')->insertGetId([
                'company_id'      => $this->companyId,
                'name'            => 'Operations Cash Float',
                'currency'        => 'CDF',
                'opening_balance' => 0,
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        } else {
            $accountId = $existing;
        }

        $txns = [
            ['replenishment', 'Monthly float top-up',           500000,   30],
            ['expense',       'Office supplies',                 -15000,   29],
            ['expense',       'Fuel for company vehicle',        -45000,   28],
            ['expense',       'Driver meal allowances',          -20000,   27],
            ['expense',       'Printer cartridges',              -12000,   26],
            ['replenishment', 'Emergency top-up',                200000,   25],
            ['expense',       'Gate pass fees — Matadi port',    -35000,   24],
            ['expense',       'Phone credit for transport team', -8000,    23],
            ['expense',       'Stationery and forms',            -5500,    22],
            ['expense',       'Tea and hospitality — clients',   -18000,   21],
            ['replenishment', 'Monthly float top-up',            500000,   20],
            ['expense',       'Vehicle maintenance',             -75000,   19],
            ['expense',       'Internet bundles',                -22000,   18],
            ['expense',       'Cleaning services',               -15000,   17],
            ['expense',       'Parking fees',                    -4000,    16],
        ];

        foreach ($txns as [$type, $desc, $amount, $daysAgo]) {
            DB::table('petty_cash_transactions')->insert([
                'company_id'      => $this->companyId,
                'account_id'      => $accountId,
                'type'            => $type,
                'description'     => $desc,
                'amount'          => $amount,
                'currency'        => 'CDF',
                'transaction_date'=> Carbon::now()->subDays($daysAgo)->toDateString(),
                'created_by'      => 1,
                'created_at'      => Carbon::now()->subDays($daysAgo),
                'updated_at'      => Carbon::now()->subDays($daysAgo),
            ]);
        }

        $this->command->info('Petty cash: done');
    }
}
