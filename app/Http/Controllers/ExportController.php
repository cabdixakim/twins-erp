<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use ZipArchive;

class ExportController extends Controller
{
    private int $companyId;

    // Tables → [filename_label, method_or_null_for_plain]
    private array $sheets = [
        'companies'                  => 'Companies',
        'users'                      => 'Users',
        'products'                   => 'Products',
        'depots'                     => 'Depots',
        'suppliers'                  => 'Suppliers',
        'transporters'               => 'Transporters',
        'clients'                    => 'Clients',
        'inventory_periods'          => 'Inventory_Periods',
        'purchases'                  => 'Purchases',
        'batches'                    => 'Batches',
        'batch_costs'                => 'Batch_Costs',
        'import_nominations'         => 'Import_Nominations',
        'import_trucks'              => 'Import_Trucks',
        'depot_stocks'               => 'Depot_Stocks',
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

        foreach ($this->sheets as $table => $label) {
            $rows = $this->fetchRows($table);

            if (empty($rows)) {
                $manifest[] = "{$label}: 0 rows (skipped)";
                continue;
            }

            $csvPath = "{$tmpDir}/{$label}.csv";
            $fh      = fopen($csvPath, 'w');
            fputcsv($fh, array_keys((array) $rows[0]));
            foreach ($rows as $row) {
                fputcsv($fh, array_values((array) $row));
            }
            fclose($fh);

            $zip->addFile($csvPath, "{$label}.csv");
            $manifest[] = "{$label}: " . count($rows) . " rows";
        }

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

    // ─── Router ───────────────────────────────────────────────────────────────

    private function fetchRows(string $table): array
    {
        $method = 'rows' . str_replace('_', '', ucwords($table, '_'));
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return $this->plainRows($table);
    }

    private function plainRows(string $table): array
    {
        $exists = DB::selectOne(
            "SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name=?",
            [$table]
        );
        if (!$exists) return [];
        return DB::select("SELECT * FROM \"{$table}\" WHERE company_id = ?", [$this->companyId]);
    }

    // ─── Enriched queries — one method per table ──────────────────────────────

    private function rowsCompanies(): array
    {
        return DB::select("
            SELECT id, name, code, country, address, phone, email, website,
                   base_currency, costing_method, timezone,
                   rccm, id_nat, nif, created_at
            FROM companies WHERE id = ?
        ", [$this->companyId]);
    }

    private function rowsUsers(): array
    {
        return DB::select("
            SELECT u.name, u.email, u.created_at
            FROM users u
            INNER JOIN company_user cu ON cu.user_id = u.id AND cu.company_id = ?
            ORDER BY u.name
        ", [$this->companyId]);
    }

    private function rowsProducts(): array
    {
        return DB::select("
            SELECT name, code, category, base_uom, allowed_loss_pct,
                   default_density, is_active, created_at
            FROM products WHERE company_id = ? ORDER BY name
        ", [$this->companyId]);
    }

    private function rowsDepots(): array
    {
        return DB::select("
            SELECT name, city, contact_person, phone,
                   storage_fee_per_1000_l, default_currency,
                   default_shrinkage_pct, is_active,
                   CASE WHEN is_system THEN 'Yes' ELSE 'No' END AS system_depot,
                   notes, created_at
            FROM depots WHERE company_id = ? ORDER BY name
        ", [$this->companyId]);
    }

    private function rowsSuppliers(): array
    {
        return DB::select("
            SELECT name, type, country, city, contact_person, phone,
                   email, default_currency, is_active, notes, created_at
            FROM suppliers WHERE company_id = ? ORDER BY name
        ", [$this->companyId]);
    }

    private function rowsTransporters(): array
    {
        return DB::select("
            SELECT name, type, country, city, contact_person, phone, email,
                   default_currency, default_rate_per_1000_l, payment_terms,
                   is_active, notes, created_at
            FROM transporters WHERE company_id = ? ORDER BY name
        ", [$this->companyId]);
    }

    private function rowsClients(): array
    {
        return DB::select("
            SELECT name, code, type, country, city, contact_person, phone,
                   email, currency, credit_limit, is_active, notes, created_at
            FROM clients WHERE company_id = ? ORDER BY name
        ", [$this->companyId]);
    }

    private function rowsInventoryPeriods(): array
    {
        return DB::select("
            SELECT name, costing_method, status, starts_at, ends_at,
                   created_at, closed_at
            FROM inventory_periods WHERE company_id = ? ORDER BY starts_at
        ", [$this->companyId]);
    }

    private function rowsPurchases(): array
    {
        return DB::select("
            SELECT
                p.reference,
                p.type,
                p.status,
                p.purchase_date,
                s.name                          AS supplier,
                prod.name                       AS product,
                prod.code                       AS product_code,
                p.qty,
                p.unit_price,
                p.currency,
                ROUND((p.qty * p.unit_price)::numeric, 2) AS total_value,
                d.name                          AS depot,
                b.code                          AS batch_code,
                c.name                          AS client,
                t.name                          AS transporter,
                p.vessel_name,
                p.voyage_no,
                p.loading_port,
                p.discharge_port,
                p.bl_number,
                p.bl_date,
                p.eta_date,
                p.qty_delivered,
                p.freight_amount,
                p.freight_currency,
                p.notes,
                p.action_note,
                p.actioned_at,
                p.created_at
            FROM purchases p
            LEFT JOIN suppliers    s    ON s.id    = p.supplier_id
            LEFT JOIN products     prod ON prod.id = p.product_id
            LEFT JOIN depots       d    ON d.id    = p.depot_id
            LEFT JOIN batches      b    ON b.id    = p.batch_id
            LEFT JOIN clients      c    ON c.id    = p.client_id
            LEFT JOIN transporters t    ON t.id    = p.transporter_id
            WHERE p.company_id = ?
            ORDER BY p.purchase_date DESC, p.sequence_no
        ", [$this->companyId]);
    }

    private function rowsBatches(): array
    {
        return DB::select("
            SELECT
                b.code,
                b.name,
                b.source_type,
                b.source_ref,
                b.status,
                prod.name                       AS product,
                prod.code                       AS product_code,
                s.name                          AS supplier,
                b.qty_purchased,
                b.qty_received,
                b.qty_remaining,
                b.unit_cost,
                b.total_cost,
                b.purchased_at,
                b.created_at
            FROM batches b
            LEFT JOIN products  prod ON prod.id = b.product_id
            LEFT JOIN suppliers s    ON s.id    = b.supplier_id
            WHERE b.company_id = ?
            ORDER BY b.purchased_at DESC
        ", [$this->companyId]);
    }

    private function rowsBatchCosts(): array
    {
        return DB::select("
            SELECT
                p.reference                     AS purchase_reference,
                b.code                          AS batch_code,
                bc.category,
                bc.description,
                bc.amount,
                bc.currency,
                bc.exchange_rate,
                bc.amount_base,
                CASE WHEN bc.is_included_in_cost THEN 'Yes' ELSE 'No' END AS included_in_cost,
                bc.entry_date,
                bc.created_at
            FROM batch_costs bc
            LEFT JOIN batches   b ON b.id = bc.batch_id
            LEFT JOIN purchases p ON p.id = bc.purchase_id
            WHERE bc.company_id = ?
            ORDER BY bc.entry_date DESC
        ", [$this->companyId]);
    }

    private function rowsImportNominations(): array
    {
        return DB::select("
            SELECT
                p.reference                     AS purchase_reference,
                t.name                          AS transporter,
                n.rate_per_1000l,
                n.currency,
                n.allowed_loss_pct,
                n.short_charge_rate,
                n.short_charge_currency,
                n.advances,
                n.advances_currency,
                n.status,
                n.notes,
                n.created_at
            FROM import_nominations n
            LEFT JOIN purchases    p ON p.id = n.purchase_id
            LEFT JOIN transporters t ON t.id = n.transporter_id
            WHERE n.company_id = ?
            ORDER BY n.created_at DESC
        ", [$this->companyId]);
    }

    private function rowsImportTrucks(): array
    {
        return DB::select("
            SELECT
                p.reference                     AS purchase_reference,
                tk.truck_reg,
                tk.trailer_reg,
                tk.driver_name,
                tk.driver_phone,
                tk.capacity,
                tk.qty_loaded,
                tk.qty_delivered,
                tk.shortfall_qty,
                tk.allowed_loss_qty,
                tk.excess_loss_qty,
                tk.shortfall_charge,
                tk.status,
                d.name                          AS delivery_depot,
                tk.pickup_date,
                tk.delivery_date,
                tk.border_date,
                tk.tr8_number,
                tk.t1_number,
                tk.load_notes,
                tk.delivery_notes,
                tk.notes,
                tk.failure_reason,
                tk.created_at
            FROM import_trucks tk
            LEFT JOIN import_nominations n ON n.id = tk.nomination_id
            LEFT JOIN purchases          p ON p.id = n.purchase_id
            LEFT JOIN depots             d ON d.id = tk.depot_id
            WHERE tk.company_id = ?
            ORDER BY tk.created_at
        ", [$this->companyId]);
    }

    private function rowsDepotStocks(): array
    {
        return DB::select("
            SELECT
                d.name                          AS depot,
                prod.name                       AS product,
                prod.code                       AS product_code,
                b.code                          AS batch_code,
                ds.qty_on_hand,
                ds.qty_reserved,
                ROUND((ds.qty_on_hand - ds.qty_reserved)::numeric, 0) AS qty_available,
                ds.unit_cost,
                ROUND((ds.qty_on_hand * ds.unit_cost)::numeric, 2)    AS stock_value,
                ds.updated_at
            FROM depot_stocks ds
            LEFT JOIN depots   d    ON d.id    = ds.depot_id
            LEFT JOIN products prod ON prod.id = ds.product_id
            LEFT JOIN batches  b    ON b.id    = ds.batch_id
            WHERE ds.company_id = ?
            ORDER BY d.name, prod.name
        ", [$this->companyId]);
    }

    private function rowsInventoryMovements(): array
    {
        return DB::select("
            SELECT
                m.type,
                m.reference,
                m.created_at                    AS movement_date,
                prod.name                       AS product,
                prod.code                       AS product_code,
                b.code                          AS batch_code,
                fd.name                         AS from_depot,
                td.name                         AS to_depot,
                m.qty,
                m.unit_cost,
                m.total_cost,
                ip.name                         AS period,
                m.notes
            FROM inventory_movements m
            LEFT JOIN products          prod ON prod.id = m.product_id
            LEFT JOIN batches           b    ON b.id    = m.batch_id
            LEFT JOIN depots            fd   ON fd.id   = m.from_depot_id
            LEFT JOIN depots            td   ON td.id   = m.to_depot_id
            LEFT JOIN inventory_periods ip   ON ip.id   = m.period_id
            WHERE m.company_id = ?
            ORDER BY m.created_at DESC
        ", [$this->companyId]);
    }

    private function rowsInventoryConsumptions(): array
    {
        return DB::select("
            SELECT
                ic.created_at                   AS date,
                prod.name                       AS product,
                b.code                          AS batch_code,
                ic.qty,
                ic.unit_cost,
                ic.total_cost
            FROM inventory_consumptions ic
            LEFT JOIN products prod ON prod.id = ic.product_id
            LEFT JOIN batches  b    ON b.id    = ic.batch_id
            WHERE ic.company_id = ?
            ORDER BY ic.created_at DESC
        ", [$this->companyId]);
    }

    private function rowsSales(): array
    {
        return DB::select("
            SELECT
                s.reference,
                s.sale_date,
                s.status,
                prod.name                       AS product,
                prod.code                       AS product_code,
                d.name                          AS depot,
                c.name                          AS client,
                t.name                          AS transporter,
                s.qty,
                s.unit_price,
                s.currency,
                s.total,
                s.cogs_total,
                s.gross_profit,
                s.delivery_mode,
                s.truck_no,
                s.trailer_no,
                s.waybill_no,
                s.driver_name,
                s.qty_delivered,
                s.freight_amount,
                s.freight_currency,
                s.seal_numbers,
                s.temperature,
                s.density,
                s.delivery_notes,
                s.posted_at,
                s.created_at
            FROM sales s
            LEFT JOIN products     prod ON prod.id = s.product_id
            LEFT JOIN depots       d    ON d.id    = s.depot_id
            LEFT JOIN clients      c    ON c.id    = s.client_id
            LEFT JOIN transporters t    ON t.id    = s.transporter_id
            WHERE s.company_id = ?
            ORDER BY s.sale_date DESC, s.sequence_no
        ", [$this->companyId]);
    }

    private function rowsInvoices(): array
    {
        return DB::select("
            SELECT
                inv.invoice_number,
                inv.type,
                inv.status,
                inv.issued_date,
                inv.due_date,
                c.name                          AS client,
                s.reference                     AS sale_reference,
                inv.currency,
                inv.subtotal,
                inv.tax_rate,
                inv.tax_amount,
                inv.discount_amount,
                inv.total,
                inv.paid_amount,
                ROUND((inv.total - inv.paid_amount)::numeric, 2) AS balance_due,
                inv.payment_terms,
                inv.paid_at,
                inv.notes,
                inv.created_at
            FROM invoices inv
            LEFT JOIN clients c ON c.id = inv.client_id
            LEFT JOIN sales   s ON s.id = inv.sale_id
            WHERE inv.company_id = ?
            ORDER BY inv.issued_date DESC
        ", [$this->companyId]);
    }

    private function rowsInvoiceItems(): array
    {
        return DB::select("
            SELECT
                inv.invoice_number,
                ii.sort_order,
                ii.description,
                ii.qty,
                ii.unit_price,
                ii.amount,
                inv.currency,
                inv.issued_date
            FROM invoice_items ii
            INNER JOIN invoices inv ON inv.id = ii.invoice_id
            WHERE inv.company_id = ?
            ORDER BY inv.issued_date DESC, inv.invoice_number, ii.sort_order
        ", [$this->companyId]);
    }

    private function rowsSupplierLedgerEntries(): array
    {
        return DB::select("
            SELECT
                sle.entry_date,
                s.name                          AS supplier,
                sle.type,
                sle.description,
                sle.amount,
                sle.currency,
                sle.ref_type,
                sle.created_at
            FROM supplier_ledger_entries sle
            LEFT JOIN suppliers s ON s.id = sle.supplier_id
            WHERE sle.company_id = ?
            ORDER BY sle.entry_date DESC
        ", [$this->companyId]);
    }

    private function rowsDepotLedgerEntries(): array
    {
        return DB::select("
            SELECT
                dle.entry_date,
                d.name                          AS depot,
                dle.type,
                dle.description,
                dle.amount,
                dle.currency,
                dle.ref_type,
                dle.created_at
            FROM depot_ledger_entries dle
            LEFT JOIN depots d ON d.id = dle.depot_id
            WHERE dle.company_id = ?
            ORDER BY dle.entry_date DESC
        ", [$this->companyId]);
    }

    private function rowsTransporterLedgerEntries(): array
    {
        return DB::select("
            SELECT
                tle.entry_date,
                t.name                          AS transporter,
                tle.type,
                tle.advance_type,
                tle.description,
                tle.amount,
                tle.currency,
                tle.ref_type,
                tle.created_at
            FROM transporter_ledger_entries tle
            LEFT JOIN transporters t ON t.id = tle.transporter_id
            WHERE tle.company_id = ?
            ORDER BY tle.entry_date DESC
        ", [$this->companyId]);
    }

    private function rowsClientLedgerEntries(): array
    {
        return DB::select("
            SELECT
                cle.entry_date,
                c.name                          AS client,
                cle.type,
                cle.description,
                cle.amount,
                cle.currency,
                cle.ref_type,
                cle.created_at
            FROM client_ledger_entries cle
            LEFT JOIN clients c ON c.id = cle.client_id
            WHERE cle.company_id = ?
            ORDER BY cle.entry_date DESC
        ", [$this->companyId]);
    }

    private function rowsPettyCashAccounts(): array
    {
        return DB::select("
            SELECT name, currency, opening_balance, is_active, created_at
            FROM petty_cash_accounts WHERE company_id = ? ORDER BY name
        ", [$this->companyId]);
    }

    private function rowsPettyCashTransactions(): array
    {
        return DB::select("
            SELECT
                pt.transaction_date,
                a.name                          AS account,
                pt.type,
                pt.category,
                pt.description,
                pt.recipient,
                pt.amount,
                pt.currency,
                pt.reference,
                pt.created_at
            FROM petty_cash_transactions pt
            LEFT JOIN petty_cash_accounts a ON a.id = pt.account_id
            WHERE pt.company_id = ?
            ORDER BY pt.transaction_date DESC
        ", [$this->companyId]);
    }

    private function rowsBankAccounts(): array
    {
        return DB::select("
            SELECT name, bank_name, account_number, currency,
                   opening_balance, is_active, created_at
            FROM bank_accounts WHERE company_id = ? ORDER BY name
        ", [$this->companyId]);
    }

    private function rowsBankTransactions(): array
    {
        return DB::select("
            SELECT
                bt.entry_date,
                ba.name                         AS bank_account,
                ba.bank_name,
                bt.type,
                bt.description,
                bt.amount,
                bt.currency,
                bt.exchange_rate,
                bt.reference,
                bt.ref_type,
                CASE WHEN bt.is_reconciled THEN 'Yes' ELSE 'No' END AS reconciled,
                bt.reconciled_at,
                bt.statement_ref,
                bt.created_at
            FROM bank_transactions bt
            LEFT JOIN bank_accounts ba ON ba.id = bt.bank_account_id
            WHERE bt.company_id = ?
            ORDER BY bt.entry_date DESC
        ", [$this->companyId]);
    }

    private function rowsAuditLogs(): array
    {
        return $this->plainRows('audit_logs');
    }
}
