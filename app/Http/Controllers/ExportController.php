<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use ZipArchive;

class ExportController extends Controller
{
    private int $companyId;

    private array $tables = [
        'companies'                  => 'Companies',
        'users'                      => 'Users',
        'products'                   => 'Products',
        'depots'                     => 'Depots',
        'suppliers'                  => 'Suppliers',
        'transporters'               => 'Transporters',
        'clients'                    => 'Clients',
        'purchases'                  => 'Purchases',
        'import_nominations'         => 'Import_Nominations',
        'import_trucks'              => 'Import_Trucks',
        'batches'                    => 'Batches',
        'batch_costs'                => 'Batch_Costs',
        'depot_stocks'               => 'Depot_Stocks',
        'inventory_periods'          => 'Inventory_Periods',
        'inventory_movements'        => 'Inventory_Movements',
        'inventory_consumptions'     => 'Inventory_Consumptions',
        'sales'                      => 'Sales',
        'invoices'                   => 'Invoices',
        'invoice_items'              => 'Invoice_Items',
        'supplier_ledger_entries'    => 'Supplier_Ledger',
        'depot_ledger_entries'       => 'Depot_Ledger',
        'transporter_ledger_entries' => 'Transporter_Ledger',
        'client_ledger_entries'      => 'Client_Ledger',
        'petty_cash_accounts'        => 'Petty_Cash_Accounts',
        'petty_cash_transactions'    => 'Petty_Cash_Transactions',
        'bank_accounts'              => 'Bank_Accounts',
        'bank_transactions'          => 'Bank_Transactions',
        'audit_logs'                 => 'Audit_Log',
    ];

    // Tables that are scoped to the company via company_id column
    private array $companyScoped = [
        'products', 'depots', 'suppliers', 'transporters', 'clients',
        'purchases', 'batches', 'batch_costs', 'depot_stocks',
        'inventory_periods', 'inventory_movements', 'inventory_consumptions',
        'sales', 'invoices', 'invoice_items',
        'supplier_ledger_entries', 'depot_ledger_entries',
        'transporter_ledger_entries', 'client_ledger_entries',
        'petty_cash_accounts', 'petty_cash_transactions',
        'bank_accounts', 'bank_transactions', 'audit_logs',
        'import_nominations', 'import_trucks',
    ];

    public function fullDump()
    {
        $user            = auth()->user();
        $this->companyId = (int) ($user?->active_company_id ?? 0);

        $tmpDir  = sys_get_temp_dir() . '/twins_export_' . uniqid();
        $zipPath = $tmpDir . '.zip';
        mkdir($tmpDir, 0755, true);

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $manifest = [];

        foreach ($this->tables as $table => $label) {
            $rows = $this->fetchRows($table);
            if ($rows->isEmpty()) {
                $manifest[] = "{$label}: 0 rows (skipped)";
                continue;
            }

            $csvPath = "{$tmpDir}/{$label}.csv";
            $fh      = fopen($csvPath, 'w');

            // Header row
            fputcsv($fh, array_keys((array) $rows->first()));

            foreach ($rows as $row) {
                fputcsv($fh, array_values((array) $row));
            }

            fclose($fh);
            $zip->addFile($csvPath, "{$label}.csv");
            $manifest[] = "{$label}: {$rows->count()} rows";
        }

        // Add a manifest.txt
        $manifestContent  = "Twins ERP — Full Export\n";
        $manifestContent .= "Generated: " . now()->toDateTimeString() . "\n";
        $manifestContent .= "Company ID: {$this->companyId}\n\n";
        $manifestContent .= implode("\n", $manifest);

        $zip->addFromString('_manifest.txt', $manifestContent);
        $zip->close();

        $filename = 'twins-export-' . now()->format('Y-m-d_His') . '.zip';

        return response()->download($zipPath, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    private function fetchRows(string $table)
    {
        // Check table exists
        $exists = DB::select(
            "SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name=?",
            [$table]
        );
        if (!$exists) {
            return collect();
        }

        $query = DB::table($table);

        // For companies, only export the active company
        if ($table === 'companies' && $this->companyId) {
            $query->where('id', $this->companyId);
            return $query->get();
        }

        // For users, only export users in the active company
        if ($table === 'users' && $this->companyId) {
            $query->whereIn('id', function ($sub) {
                $sub->select('user_id')
                    ->from('company_user')
                    ->where('company_id', $this->companyId);
            });
            return $query->get();
        }

        // For import_nominations: scope via purchases.company_id
        if ($table === 'import_nominations' && $this->companyId) {
            $query->whereIn('purchase_id', function ($sub) {
                $sub->select('id')->from('purchases')->where('company_id', $this->companyId);
            });
            return $query->get();
        }

        // For import_trucks: scope via import_nominations → purchases
        if ($table === 'import_trucks' && $this->companyId) {
            $query->whereIn('nomination_id', function ($sub) {
                $sub->select('id')->from('import_nominations')
                    ->whereIn('purchase_id', function ($sub2) {
                        $sub2->select('id')->from('purchases')->where('company_id', $this->companyId);
                    });
            });
            return $query->get();
        }

        // invoice_items: scope via invoices.company_id
        if ($table === 'invoice_items' && $this->companyId) {
            $query->whereIn('invoice_id', function ($sub) {
                $sub->select('id')->from('invoices')->where('company_id', $this->companyId);
            });
            return $query->get();
        }

        // batch_costs: scope via batches.company_id
        if ($table === 'batch_costs' && $this->companyId) {
            $query->whereIn('batch_id', function ($sub) {
                $sub->select('id')->from('batches')->where('company_id', $this->companyId);
            });
            return $query->get();
        }

        // Standard company_id scoped tables
        if (in_array($table, $this->companyScoped, true) && $this->companyId) {
            $query->where('company_id', $this->companyId);
        }

        return $query->get();
    }
}
