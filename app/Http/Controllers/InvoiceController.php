<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ClientLedgerEntry;
use App\Models\Invoice;
use App\Services\JournalAutoPost;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    private function company()
    {
        return auth()->user()->activeCompany;
    }

    public function index(Request $request)
    {
        $company = $this->company();

        $q = Invoice::where('company_id', $company->id)
            ->where('type', 'invoice')
            ->with(['client', 'sale'])
            ->orderByDesc('issued_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('client_id')) {
            $q->where('client_id', $request->client_id);
        }
        if ($request->filled('from')) {
            $q->whereDate('issued_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('issued_date', '<=', $request->to);
        }

        $invoices = $q->paginate(25)->withQueryString();

        Invoice::where('company_id', $company->id)
            ->where('status', 'sent')
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);

        $clients = \App\Models\Client::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $totals = [
            'outstanding' => Invoice::where('company_id', $company->id)
                ->whereIn('status', ['sent', 'overdue'])
                ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as total')
                ->value('total'),
            'overdue' => Invoice::where('company_id', $company->id)
                ->where('status', 'overdue')
                ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as total')
                ->value('total'),
            'paid_this_month' => Invoice::where('company_id', $company->id)
                ->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('total'),
        ];

        return view('invoices.index', compact('invoices', 'clients', 'totals', 'company'));
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $invoice->load(['client', 'company', 'items', 'sale']);

        if ($invoice->status === 'sent' && $invoice->due_date->isPast()) {
            $invoice->update(['status' => 'overdue']);
            $invoice->refresh();
        }

        $creditNotes = Invoice::where('credit_note_for', $invoice->id)
            ->orderByDesc('issued_date')
            ->get();

        $auditLogs = AuditLog::where('company_id', $invoice->company_id)
            ->where('model_type', Invoice::class)
            ->where('model_id', $invoice->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('invoices.show', compact('invoice', 'creditNotes', 'auditLogs'));
    }

    public function downloadPdf(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $invoice->load(['client', 'company', 'items', 'sale']);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        $pdf->setPaper('A4', 'portrait');

        $filename = $invoice->invoice_number . '.pdf';

        return $pdf->download($filename);
    }

    public function updateNotes(Request $request, Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        if (in_array($invoice->status, ['void'], true)) {
            return back()->with('error', 'Cannot edit a voided invoice.');
        }

        $data = $request->validate([
            'notes'         => 'nullable|string|max:2000',
            'footer_text'   => 'nullable|string|max:2000',
            'bank_details'  => 'nullable|string|max:2000',
            'payment_terms' => 'nullable|string|max:100',
            'due_date'      => 'nullable|date',
        ]);

        $invoice->update(array_filter($data, fn ($v) => $v !== null) + ['updated_by' => auth()->id()]);

        return back()->with('status', 'Invoice updated.');
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $data = $request->validate([
            'paid_amount' => 'required|numeric|min:0.01',
            'paid_at'     => 'required|date',
        ]);

        $amount  = (float) $data['paid_amount'];
        $newPaid = (float) $invoice->paid_amount + $amount;
        $status  = $newPaid >= (float) $invoice->total ? 'paid' : 'sent';
        $cid     = (int) auth()->user()->active_company_id;

        DB::transaction(function () use ($invoice, $data, $amount, $newPaid, $status, $cid) {
            $invoice->update([
                'paid_amount' => min($newPaid, (float) $invoice->total),
                'status'      => $status,
                'paid_at'     => $status === 'paid' ? $data['paid_at'] : $invoice->paid_at,
                'updated_by'  => auth()->id(),
            ]);

            // Post to client ledger
            if ($invoice->client_id) {
                $desc  = 'Payment received — ' . $invoice->invoice_number;
                $entry = ClientLedgerEntry::create([
                    'company_id'  => $cid,
                    'client_id'   => $invoice->client_id,
                    'type'        => 'payment',
                    'amount'      => -$amount,
                    'currency'    => $invoice->currency,
                    'description' => $desc,
                    'ref_type'    => Invoice::class,
                    'ref_id'      => $invoice->id,
                    'entry_date'  => $data['paid_at'],
                    'created_by'  => auth()->id(),
                ]);

                JournalAutoPost::for($cid)->postClientPayment(
                    ledgerEntryId: $entry->id,
                    reference:     'PAY-INV-' . $invoice->id,
                    amount:        $amount,
                    currency:      $invoice->currency,
                    description:   $desc,
                    date:          $data['paid_at'],
                );
            }
        });

        AuditLog::record(
            'paid',
            "Invoice {$invoice->invoice_number} marked " . ($status === 'paid' ? 'fully paid' : 'partially paid') . " — amount: {$data['paid_amount']} {$invoice->currency}",
            $invoice,
            "Invoice {$invoice->invoice_number}",
            severity: 'warning',
            before: ['paid_amount' => (float)$invoice->getOriginal('paid_amount'), 'status' => $invoice->getOriginal('status')],
            after: ['paid_amount' => min($newPaid, (float)$invoice->total), 'status' => $status],
            module: 'Invoice',
        );

        return back()->with('status', 'Payment recorded on invoice.');
    }

    public function creditNote(Request $request, Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        if ($invoice->status === 'void') {
            return back()->with('error', 'Cannot issue a credit note against a voided invoice.');
        }
        if ($invoice->type === 'credit_note') {
            return back()->with('error', 'Cannot issue a credit note against another credit note.');
        }

        $data = $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'reason'      => 'required|string|max:500',
            'issued_date' => 'required|date',
        ]);

        $amount  = min((float) $data['amount'], max(0, (float) $invoice->total - (float) $invoice->paid_amount));

        if ($amount <= 0) {
            return back()->with('error', 'Invoice is already fully paid or credited — no remaining balance to credit.');
        }

        $cid = (int) auth()->user()->active_company_id;

        DB::transaction(function () use ($invoice, $data, $amount, $cid) {
            $company = $this->company();

            $seq = Invoice::where('company_id', $company->id)
                ->where('type', 'credit_note')
                ->count() + 1;

            $cnNumber = 'CN-' . str_pad($seq, 4, '0', STR_PAD_LEFT) . '/' . now()->format('Y');

            $cn = Invoice::create([
                'company_id'      => $company->id,
                'client_id'       => $invoice->client_id,
                'sale_id'         => $invoice->sale_id,
                'type'            => 'credit_note',
                'credit_note_for' => $invoice->id,
                'invoice_number'  => $cnNumber,
                'currency'        => $invoice->currency,
                'subtotal'        => -$amount,
                'tax_rate'        => 0,
                'tax_amount'      => 0,
                'total'           => -$amount,
                'paid_amount'     => 0,
                'status'          => 'sent',
                'issued_date'     => $data['issued_date'],
                'due_date'        => $data['issued_date'],
                'notes'           => 'Credit note for ' . $invoice->invoice_number . ': ' . $data['reason'],
                'payment_terms'   => $invoice->payment_terms,
                'footer_text'     => $invoice->footer_text,
                'bank_details'    => $invoice->bank_details,
            ]);

            $newPaid   = min((float) $invoice->total, (float) $invoice->paid_amount + $amount);
            $newStatus = $newPaid >= (float) $invoice->total ? 'paid' : $invoice->status;
            $invoice->update([
                'paid_amount' => $newPaid,
                'status'      => $newStatus,
                'updated_by'  => auth()->id(),
            ]);

            // Post to client ledger
            if ($invoice->client_id) {
                $desc  = 'Credit note ' . $cnNumber . ' — ' . $invoice->invoice_number . ': ' . $data['reason'];
                $entry = ClientLedgerEntry::create([
                    'company_id'  => $cid,
                    'client_id'   => $invoice->client_id,
                    'type'        => 'credit_note',
                    'amount'      => -$amount,
                    'currency'    => $invoice->currency,
                    'description' => $desc,
                    'ref_type'    => Invoice::class,
                    'ref_id'      => $cn->id,
                    'entry_date'  => $data['issued_date'],
                    'created_by'  => auth()->id(),
                ]);

                JournalAutoPost::for($cid)->postClientCreditNote(
                    ledgerEntryId: $entry->id,
                    reference:     $cnNumber,
                    amount:        $amount,
                    currency:      $invoice->currency,
                    description:   $desc,
                    date:          $data['issued_date'],
                );
            }

            AuditLog::record(
                'credit_note',
                "Credit note {$cn->invoice_number} issued against {$invoice->invoice_number} — {$invoice->currency} " . number_format($amount, 2) . ': ' . $data['reason'],
                $invoice,
                "Invoice {$invoice->invoice_number}",
                severity: 'warning',
                after: ['credit_note' => $cn->invoice_number, 'amount' => $amount, 'reason' => $data['reason']],
                module: 'Invoice',
            );
        });

        return back()->with('status', 'Credit note issued and applied to invoice.');
    }

    public function void(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        if ($invoice->status === 'void') {
            return back()->with('error', 'Invoice is already voided.');
        }

        $invoice->update([
            'status'     => 'void',
            'updated_by' => auth()->id(),
        ]);

        AuditLog::record(
            'voided',
            "Invoice {$invoice->invoice_number} voided for client {$invoice->client?->name}",
            $invoice,
            "Invoice {$invoice->invoice_number}",
            severity: 'critical',
            before: ['status' => $invoice->getOriginal('status'), 'total' => $invoice->total],
            after: ['status' => 'void'],
            module: 'Invoice',
        );

        return back()->with('status', 'Invoice voided.');
    }

    private function authorizeCompany(Invoice $invoice): void
    {
        if ($invoice->company_id !== $this->company()->id) {
            abort(403);
        }
    }
}
