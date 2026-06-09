<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Invoice;
use Illuminate\Http\Request;

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

        // Update overdue status automatically
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

        // Auto-flag overdue
        if ($invoice->status === 'sent' && $invoice->due_date->isPast()) {
            $invoice->update(['status' => 'overdue']);
            $invoice->refresh();
        }

        return view('invoices.show', compact('invoice'));
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

        $newPaid = (float) $invoice->paid_amount + (float) $data['paid_amount'];
        $status  = $newPaid >= (float) $invoice->total ? 'paid' : 'sent';

        $invoice->update([
            'paid_amount' => min($newPaid, (float) $invoice->total),
            'status'      => $status,
            'paid_at'     => $status === 'paid' ? $data['paid_at'] : $invoice->paid_at,
            'updated_by'  => auth()->id(),
        ]);

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
