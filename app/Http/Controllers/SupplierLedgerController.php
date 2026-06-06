<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\Purchase;

class SupplierLedgerController extends Controller
{
    public function index()
    {
        $cid = (int) auth()->user()->active_company_id;

        $suppliers = Supplier::where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $balances = SupplierLedgerEntry::where('company_id', $cid)
            ->selectRaw('supplier_id, currency, SUM(amount) as balance')
            ->groupBy('supplier_id', 'currency')
            ->get()
            ->groupBy('supplier_id')
            ->map(fn($rows) => $rows->pluck('balance', 'currency'));

        $invoicedTotals = SupplierLedgerEntry::where('company_id', $cid)
            ->where('type', 'purchase_invoice')
            ->selectRaw('supplier_id, SUM(amount) as total')
            ->groupBy('supplier_id')
            ->pluck('total', 'supplier_id');

        return view('suppliers.index', compact('suppliers', 'balances', 'invoicedTotals'));
    }

    public function show(Supplier $supplier)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $supplier->company_id !== $cid, 403);

        $entries = SupplierLedgerEntry::where('company_id', $cid)
            ->where('supplier_id', $supplier->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30);

        $breakdown = SupplierLedgerEntry::where('company_id', $cid)
            ->where('supplier_id', $supplier->id)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $invoicedTotal = (float) ($breakdown['purchase_invoice'] ?? 0);
        $paymentTotal  = abs((float) ($breakdown['payment'] ?? 0));
        $creditTotal   = abs((float) ($breakdown['credit_note'] ?? 0));
        $netPayable    = (float) $breakdown->sum();
        $currency      = $supplier->default_currency ?: 'USD';

        // Build reference links to purchases
        $allEntries  = $entries->getCollection();
        $purchaseIds = $allEntries->where('ref_type', 'purchase')->pluck('ref_id')->unique()->filter();
        $purchaseRefs = Purchase::whereIn('id', $purchaseIds)->pluck('reference', 'id');

        return view('suppliers.show', compact(
            'supplier', 'entries', 'purchaseRefs',
            'invoicedTotal', 'paymentTotal', 'creditTotal', 'netPayable', 'currency'
        ));
    }

    public function recordPayment(Request $request, Supplier $supplier)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $supplier->company_id !== $cid, 403);

        $data = $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'currency'    => 'nullable|string|max:8',
            'entry_date'  => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        $currency = $data['currency'] ?: ($supplier->default_currency ?: 'USD');

        SupplierLedgerEntry::create([
            'company_id'  => $cid,
            'supplier_id' => $supplier->id,
            'type'        => 'payment',
            'amount'      => -(float) $data['amount'],
            'currency'    => $currency,
            'description' => $data['description'] ?: 'Payment to supplier',
            'entry_date'  => $data['entry_date'],
            'created_by'  => auth()->id(),
        ]);

        $sym = self::currencySymbol($currency);
        return redirect()->route('suppliers.show', $supplier)
            ->with('status', 'Payment of ' . $sym . number_format($data['amount'], 2) . ' recorded.');
    }

    public function recordCredit(Request $request, Supplier $supplier)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $supplier->company_id !== $cid, 403);

        $data = $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'currency'    => 'nullable|string|max:8',
            'entry_date'  => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        $currency = $data['currency'] ?: ($supplier->default_currency ?: 'USD');

        SupplierLedgerEntry::create([
            'company_id'  => $cid,
            'supplier_id' => $supplier->id,
            'type'        => 'credit_note',
            'amount'      => -(float) $data['amount'],
            'currency'    => $currency,
            'description' => $data['description'] ?: 'Credit note from supplier',
            'entry_date'  => $data['entry_date'],
            'created_by'  => auth()->id(),
        ]);

        return redirect()->route('suppliers.show', $supplier)
            ->with('status', 'Credit note of ' . number_format($data['amount'], 2) . ' ' . $currency . ' recorded.');
    }

    public function statement(Supplier $supplier)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $supplier->company_id !== $cid, 403);

        $company = DB::table('companies')->where('id', $cid)->first();

        $entries = SupplierLedgerEntry::where('company_id', $cid)
            ->where('supplier_id', $supplier->id)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $running = 0;
        foreach ($entries as $e) {
            $running += (float) $e->amount;
            $e->running_balance = $running;
        }

        $invoicedTotal = (float) $entries->where('type', 'purchase_invoice')->sum('amount');
        $paymentTotal  = abs((float) $entries->where('type', 'payment')->sum('amount'));
        $creditTotal   = abs((float) $entries->where('type', 'credit_note')->sum('amount'));
        $netPayable    = (float) $entries->sum('amount');
        $currency      = $supplier->default_currency ?: 'USD';

        return view('suppliers.statement', compact(
            'supplier', 'company', 'entries',
            'invoicedTotal', 'paymentTotal', 'creditTotal', 'netPayable', 'currency'
        ));
    }

    public function exportCsv(Supplier $supplier)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $supplier->company_id !== $cid, 403);

        $entries  = SupplierLedgerEntry::where('company_id', $cid)
            ->where('supplier_id', $supplier->id)
            ->orderBy('entry_date')->orderBy('id')->get();

        $running  = 0;
        $filename = 'supplier-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($supplier->name)) . '-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($entries, &$running) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Description', 'Debit', 'Credit', 'Running Balance', 'Currency']);
            foreach ($entries as $e) {
                $running += (float) $e->amount;
                $isDebit  = $e->amount > 0;
                fputcsv($out, [
                    $e->entry_date->format('Y-m-d'),
                    $e->type,
                    $e->description,
                    $isDebit  ? number_format((float) $e->amount,      2, '.', '') : '',
                    !$isDebit ? number_format(abs((float) $e->amount), 2, '.', '') : '',
                    number_format($running, 2, '.', ''),
                    $e->currency,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public static function currencySymbol(string $code): string
    {
        return match ($code) {
            'USD' => '$', 'EUR' => '€', 'GBP' => '£',
            'ZAR' => 'R ', 'CDF' => 'FC ', 'ZMW' => 'K ', 'ZWL' => 'ZWL ',
            default => $code . ' ',
        };
    }

    /**
     * Post a purchase invoice to the supplier ledger.
     * Called from PurchaseController (receive / confirm) and ImportNominationController (recordDelivery).
     * Idempotent — will not double-post the same ref_type + ref_id + type combo.
     */
    public static function postInvoice(
        int    $companyId,
        int    $supplierId,
        float  $amount,
        string $currency,
        string $description,
        string $entryDate,
        string $refType,
        int    $refId,
        ?int   $createdBy = null
    ): void {
        if ($amount <= 0 || !$supplierId) {
            return;
        }

        $alreadyPosted = SupplierLedgerEntry::where('ref_type', $refType)
            ->where('ref_id', $refId)
            ->where('type', 'purchase_invoice')
            ->exists();

        if ($alreadyPosted) {
            return;
        }

        SupplierLedgerEntry::create([
            'company_id'  => $companyId,
            'supplier_id' => $supplierId,
            'type'        => 'purchase_invoice',
            'amount'      => $amount,
            'currency'    => $currency,
            'description' => $description,
            'entry_date'  => $entryDate,
            'ref_type'    => $refType,
            'ref_id'      => $refId,
            'created_by'  => $createdBy,
        ]);
    }
}
