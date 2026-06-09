<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PettyCashAccount;
use App\Models\PettyCashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PettyCashController extends Controller
{
    private function company(): \App\Models\Company
    {
        return auth()->user()->activeCompany;
    }

    private function cid(): int
    {
        return (int) auth()->user()->active_company_id;
    }

    public function index(Request $request)
    {
        $cid = $this->cid();

        $accounts = PettyCashAccount::where('company_id', $cid)
            ->orderBy('name')
            ->get()
            ->map(function ($acc) use ($cid) {
                $txSum = PettyCashTransaction::where('company_id', $cid)
                    ->where('account_id', $acc->id)
                    ->sum('amount');
                $acc->balance = round((float)$acc->opening_balance + (float)$txSum, 2);
                return $acc;
            });

        $activeId = (int) ($request->get('account') ?? $accounts->first()?->id);
        $active   = $accounts->firstWhere('id', $activeId);

        $transactions = collect();
        $recentTotals = [];

        if ($active) {
            $transactions = PettyCashTransaction::where('company_id', $cid)
                ->where('account_id', $active->id)
                ->with('createdBy')
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->paginate(40)
                ->withQueryString();

            $recentTotals = [
                'this_month_spend' => abs(PettyCashTransaction::where('company_id', $cid)
                    ->where('account_id', $active->id)
                    ->whereIn('type', ['expense', 'transfer'])
                    ->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year)
                    ->sum('amount')),
                'this_month_topup' => PettyCashTransaction::where('company_id', $cid)
                    ->where('account_id', $active->id)
                    ->whereIn('type', ['top_up'])
                    ->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year)
                    ->sum('amount'),
            ];
        }

        return view('petty-cash.index', compact('accounts', 'active', 'transactions', 'recentTotals'));
    }

    public function storeAccount(Request $request)
    {
        $cid = $this->cid();

        $data = $request->validate([
            'name'            => 'required|string|max:200',
            'currency'        => 'required|string|max:10',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        PettyCashAccount::create([
            'company_id'      => $cid,
            'name'            => $data['name'],
            'currency'        => strtoupper($data['currency']),
            'opening_balance' => $data['opening_balance'],
            'is_active'       => true,
        ]);

        return redirect()->route('petty-cash.index')
            ->with('status', 'Account "' . $data['name'] . '" created.');
    }

    public function recordTransaction(Request $request, PettyCashAccount $account)
    {
        abort_if((int)$account->company_id !== $this->cid(), 403);

        $data = $request->validate([
            'type'             => 'required|in:top_up,expense,adjustment',
            'amount'           => 'required|numeric|min:0.01',
            'description'      => 'required|string|max:500',
            'transaction_date' => 'required|date',
            'ref_type'         => 'nullable|string|max:100',
            'ref_id'           => 'nullable|integer',
        ]);

        // Expenses and transfers are negative; top-ups and adjustments positive
        $amount = match($data['type']) {
            'expense'    => -(float) $data['amount'],
            'top_up'     => (float) $data['amount'],
            'adjustment' => (float) $data['amount'], // can be negative if user wants
            default      => (float) $data['amount'],
        };

        $tx = PettyCashTransaction::create([
            'company_id'       => $this->cid(),
            'account_id'       => $account->id,
            'type'             => $data['type'],
            'amount'           => $amount,
            'currency'         => $account->currency,
            'description'      => $data['description'],
            'transaction_date' => $data['transaction_date'],
            'ref_type'         => $data['ref_type'] ?? null,
            'ref_id'           => $data['ref_id'] ?? null,
            'created_by'       => auth()->id(),
        ]);

        $sym = $account->currency;
        AuditLog::record(
            $data['type'] === 'top_up' ? 'created' : 'posted',
            ucfirst($data['type']) . ' of ' . $sym . ' ' . number_format(abs($amount), 2) . ' on account "' . $account->name . '" — ' . $data['description'],
            $account,
            "Petty Cash: {$account->name}",
            severity: $data['type'] === 'expense' ? 'warning' : 'info',
            after: ['type' => $data['type'], 'amount' => $amount, 'date' => $data['transaction_date']],
            module: 'Admin',
        );

        return redirect()->route('petty-cash.index', ['account' => $account->id])
            ->with('status', ucfirst($data['type']) . ' recorded — ' . $sym . ' ' . number_format(abs($amount), 2) . '.');
    }

    public function voidTransaction(Request $request, PettyCashAccount $account, PettyCashTransaction $transaction)
    {
        abort_if((int)$account->company_id !== $this->cid(), 403);
        abort_if((int)$transaction->account_id !== $account->id, 403);

        $reason = trim((string)$request->input('reason', ''));
        $orig   = $transaction->amount;

        // Reverse by creating a counter-entry
        PettyCashTransaction::create([
            'company_id'       => $this->cid(),
            'account_id'       => $account->id,
            'type'             => 'adjustment',
            'amount'           => -$orig,
            'currency'         => $account->currency,
            'description'      => 'VOID: ' . $transaction->description . ($reason ? " ({$reason})" : ''),
            'transaction_date' => now()->toDateString(),
            'created_by'       => auth()->id(),
        ]);

        AuditLog::record(
            'voided',
            'Petty cash transaction voided on "' . $account->name . '" — original: ' . $account->currency . ' ' . number_format(abs($orig), 2) . ($reason ? ' — ' . $reason : ''),
            $account,
            "Petty Cash: {$account->name}",
            severity: 'critical',
            module: 'Admin',
        );

        return redirect()->route('petty-cash.index', ['account' => $account->id])
            ->with('status', 'Transaction voided via reversal entry.');
    }

    public function toggleAccount(PettyCashAccount $account)
    {
        abort_if((int)$account->company_id !== $this->cid(), 403);
        $account->update(['is_active' => !$account->is_active]);
        return back()->with('status', 'Account status updated.');
    }
}
