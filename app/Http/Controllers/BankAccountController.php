<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankAccountController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────
    // List
    // ─────────────────────────────────────────────────────────────────────
    public function index()
    {
        $cid      = (int) auth()->user()->active_company_id;
        $accounts = BankAccount::where('company_id', $cid)
            ->orderBy('name')
            ->get();

        return view('banks.index', compact('accounts'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Create / Store
    // ─────────────────────────────────────────────────────────────────────
    public function create()
    {
        return view('banks.create');
    }

    public function store(Request $request)
    {
        $cid = (int) auth()->user()->active_company_id;

        $data = $request->validate([
            'name'            => 'required|string|max:150',
            'bank_name'       => 'nullable|string|max:150',
            'account_number'  => 'nullable|string|max:80',
            'currency'        => 'required|string|max:8',
            'opening_balance' => 'nullable|numeric',
        ]);

        $data['company_id']       = $cid;
        $data['opening_balance']  = $data['opening_balance'] ?? 0;
        $data['is_active']        = true;

        $account = BankAccount::create($data);

        return redirect()->route('banks.show', $account)
            ->with('status', 'Bank account created.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Show (ledger)
    // ─────────────────────────────────────────────────────────────────────
    public function show(BankAccount $bank)
    {
        $this->authorise($bank);

        $transactions = $bank->transactions()
            ->with(['createdBy', 'transferAccount', 'voidedBy'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(50);

        $balance = $bank->currentBalance();

        $otherAccounts = BankAccount::where('company_id', $bank->company_id)
            ->where('id', '!=', $bank->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('banks.show', compact('bank', 'transactions', 'balance', 'otherAccounts'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Edit / Update
    // ─────────────────────────────────────────────────────────────────────
    public function edit(BankAccount $bank)
    {
        $this->authorise($bank);
        return view('banks.edit', compact('bank'));
    }

    public function update(Request $request, BankAccount $bank)
    {
        $this->authorise($bank);

        $data = $request->validate([
            'name'            => 'required|string|max:150',
            'bank_name'       => 'nullable|string|max:150',
            'account_number'  => 'nullable|string|max:80',
            'currency'        => 'required|string|max:8',
            'opening_balance' => 'nullable|numeric',
        ]);

        $bank->update($data);

        return redirect()->route('banks.show', $bank)
            ->with('status', 'Account updated.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Toggle active
    // ─────────────────────────────────────────────────────────────────────
    public function toggleActive(BankAccount $bank)
    {
        $this->authorise($bank);
        $bank->update(['is_active' => !$bank->is_active]);
        return back()->with('status', $bank->is_active ? 'Account activated.' : 'Account deactivated.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Record transaction (deposit / withdrawal / transfer)
    // ─────────────────────────────────────────────────────────────────────
    public function recordTransaction(Request $request, BankAccount $bank)
    {
        $this->authorise($bank);

        $data = $request->validate([
            'type'               => 'required|in:deposit,withdrawal,transfer',
            'amount'             => 'required|numeric|min:0.0001',
            'entry_date'         => 'required|date',
            'reference'          => 'nullable|string|max:80',
            'description'        => 'nullable|string|max:500',
            'transfer_account_id'=> 'required_if:type,transfer|nullable|exists:bank_accounts,id',
        ]);

        $uid = auth()->id();

        DB::transaction(function () use ($data, $bank, $uid) {
            if ($data['type'] === 'transfer') {
                $toAccount = BankAccount::findOrFail($data['transfer_account_id']);
                $this->authorise($toAccount);

                $out = BankTransaction::create([
                    'company_id'          => $bank->company_id,
                    'bank_account_id'     => $bank->id,
                    'type'                => 'transfer_out',
                    'amount'              => $data['amount'],
                    'currency'            => $bank->currency,
                    'entry_date'          => $data['entry_date'],
                    'reference'           => $data['reference'] ?? null,
                    'description'         => $data['description'] ?? null,
                    'transfer_account_id' => $toAccount->id,
                    'created_by'          => $uid,
                ]);

                $in = BankTransaction::create([
                    'company_id'              => $bank->company_id,
                    'bank_account_id'         => $toAccount->id,
                    'type'                    => 'transfer_in',
                    'amount'                  => $data['amount'],
                    'currency'                => $toAccount->currency,
                    'entry_date'              => $data['entry_date'],
                    'reference'               => $data['reference'] ?? null,
                    'description'             => $data['description'] ?? null,
                    'transfer_account_id'     => $bank->id,
                    'transfer_transaction_id' => $out->id,
                    'created_by'              => $uid,
                ]);

                $out->update(['transfer_transaction_id' => $in->id]);

            } else {
                BankTransaction::create([
                    'company_id'      => $bank->company_id,
                    'bank_account_id' => $bank->id,
                    'type'            => $data['type'],
                    'amount'          => $data['amount'],
                    'currency'        => $bank->currency,
                    'entry_date'      => $data['entry_date'],
                    'reference'       => $data['reference'] ?? null,
                    'description'     => $data['description'] ?? null,
                    'created_by'      => $uid,
                ]);
            }
        });

        return back()->with('status', 'Transaction recorded.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Void transaction
    // ─────────────────────────────────────────────────────────────────────
    public function voidTransaction(Request $request, BankAccount $bank, BankTransaction $transaction)
    {
        $this->authorise($bank);

        if ($transaction->bank_account_id !== $bank->id) {
            abort(403);
        }

        if ($transaction->isVoided()) {
            return back()->withErrors(['error' => 'Transaction is already voided.']);
        }

        $data = $request->validate([
            'void_reason' => 'nullable|string|max:300',
        ]);

        $uid = auth()->id();
        $now = now();

        DB::transaction(function () use ($transaction, $data, $uid, $now) {
            $transaction->update([
                'voided_at'   => $now,
                'voided_by'   => $uid,
                'void_reason' => $data['void_reason'] ?? null,
            ]);

            // Void counterpart if this is a transfer
            if ($transaction->transfer_transaction_id) {
                BankTransaction::where('id', $transaction->transfer_transaction_id)
                    ->whereNull('voided_at')
                    ->update([
                        'voided_at'   => $now,
                        'voided_by'   => $uid,
                        'void_reason' => 'Voided with counterpart transaction #' . $transaction->id,
                    ]);
            }
        });

        return back()->with('status', 'Transaction voided.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Export CSV
    // ─────────────────────────────────────────────────────────────────────
    public function exportCsv(BankAccount $bank)
    {
        $this->authorise($bank);

        $rows = $bank->transactions()
            ->with('createdBy')
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . str($bank->name)->slug() . '-transactions.csv"',
        ];

        $callback = function () use ($rows, $bank) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Reference', 'Description', 'Amount', 'Currency', 'Balance After', 'Voided', 'Created By']);
            $running = (float) $bank->opening_balance;
            foreach ($rows as $r) {
                if (!$r->isVoided()) {
                    $running += $r->signedAmount();
                }
                fputcsv($out, [
                    $r->entry_date->format('Y-m-d'),
                    $r->type,
                    $r->reference ?? '',
                    $r->description ?? '',
                    number_format($r->amount, 4, '.', ''),
                    $r->currency,
                    number_format($r->isVoided() ? 0 : $running, 4, '.', ''),
                    $r->isVoided() ? 'Yes' : 'No',
                    $r->createdBy?->name ?? '',
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Reconcile transactions against a bank statement
    // ─────────────────────────────────────────────────────────────────────
    public function reconcile(Request $request, BankAccount $bank)
    {
        $this->authorise($bank);

        $data = $request->validate([
            'statement_ref'    => 'nullable|string|max:100',
            'statement_balance'=> 'nullable|numeric',
            'transaction_ids'  => 'required|array|min:1',
            'transaction_ids.*'=> 'integer|exists:bank_transactions,id',
            'action'           => 'required|in:reconcile,unreconcile',
        ]);

        $uid = auth()->id();
        $now = now();

        $transactions = BankTransaction::whereIn('id', $data['transaction_ids'])
            ->where('bank_account_id', $bank->id)
            ->get();

        if ($transactions->count() !== count($data['transaction_ids'])) {
            return back()->withErrors(['error' => 'One or more transactions do not belong to this account.']);
        }

        if ($data['action'] === 'reconcile') {
            foreach ($transactions as $tx) {
                $tx->update([
                    'is_reconciled'  => true,
                    'reconciled_at'  => $now,
                    'reconciled_by'  => $uid,
                    'statement_ref'  => $data['statement_ref'] ?? null,
                ]);
            }
            $count = $transactions->count();
            return back()->with('status', $count . ' transaction' . ($count > 1 ? 's' : '') . ' marked as reconciled.');
        } else {
            foreach ($transactions as $tx) {
                $tx->update([
                    'is_reconciled' => false,
                    'reconciled_at' => null,
                    'reconciled_by' => null,
                    'statement_ref' => null,
                ]);
            }
            $count = $transactions->count();
            return back()->with('status', $count . ' transaction' . ($count > 1 ? 's' : '') . ' marked as unreconciled.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────
    private function authorise(BankAccount $bank): void
    {
        $cid = (int) auth()->user()->active_company_id;
        if ($bank->company_id !== $cid) {
            abort(403);
        }
    }
}
