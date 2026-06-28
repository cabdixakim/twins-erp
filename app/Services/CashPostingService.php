<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\PettyCashAccount;
use App\Models\PettyCashTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Shared helper that posts the cash side of a ledger payment/receipt.
 *
 * Payables  (suppliers, transporters): money leaves bank/petty cash.
 * Receivables (clients):               money enters bank/petty cash.
 */
class CashPostingService
{
    /**
     * Post cash OUT — paying a supplier or transporter.
     * Returns ['bank_transaction_id' => ?int, 'petty_cash_transaction_id' => ?int]
     */
    public static function postPayment(
        int     $companyId,
        float   $amount,
        string  $currency,
        string  $date,
        string  $description,
        string  $refType,
        int     $refId,
        ?int    $bankAccountId,
        ?int    $pettyCashAccountId,
        int     $createdBy,
    ): array {
        $bankTxId  = null;
        $pettyCashTxId = null;

        if ($bankAccountId) {
            $bank = BankAccount::where('id', $bankAccountId)
                ->where('company_id', $companyId)
                ->firstOrFail();

            $bankTx = BankTransaction::create([
                'company_id'      => $companyId,
                'bank_account_id' => $bank->id,
                'type'            => 'withdrawal',
                'amount'          => $amount,
                'currency'        => $bank->currency,
                'entry_date'      => $date,
                'description'     => $description,
                'ref_type'        => $refType,
                'ref_id'          => $refId,
                'created_by'      => $createdBy,
            ]);
            $bankTxId = $bankTx->id;
        }

        if ($pettyCashAccountId) {
            $account = PettyCashAccount::where('id', $pettyCashAccountId)
                ->where('company_id', $companyId)
                ->firstOrFail();

            $pettyCashTx = PettyCashTransaction::create([
                'company_id'       => $companyId,
                'account_id'       => $account->id,
                'type'             => 'expense',
                'amount'           => -(float) $amount,
                'currency'         => $currency,
                'description'      => $description,
                'transaction_date' => $date,
                'ref_type'         => $refType,
                'ref_id'           => $refId,
                'created_by'       => $createdBy,
            ]);
            $pettyCashTxId = $pettyCashTx->id;
        }

        return [
            'bank_transaction_id'       => $bankTxId,
            'petty_cash_transaction_id' => $pettyCashTxId,
        ];
    }

    /**
     * Post cash IN — receiving payment from a client.
     * Returns ['bank_transaction_id' => ?int, 'petty_cash_transaction_id' => ?int]
     */
    public static function postReceipt(
        int     $companyId,
        float   $amount,
        string  $currency,
        string  $date,
        string  $description,
        string  $refType,
        int     $refId,
        ?int    $bankAccountId,
        ?int    $pettyCashAccountId,
        int     $createdBy,
    ): array {
        $bankTxId      = null;
        $pettyCashTxId = null;

        if ($bankAccountId) {
            $bank = BankAccount::where('id', $bankAccountId)
                ->where('company_id', $companyId)
                ->firstOrFail();

            $bankTx = BankTransaction::create([
                'company_id'      => $companyId,
                'bank_account_id' => $bank->id,
                'type'            => 'deposit',
                'amount'          => $amount,
                'currency'        => $bank->currency,
                'entry_date'      => $date,
                'description'     => $description,
                'ref_type'        => $refType,
                'ref_id'          => $refId,
                'created_by'      => $createdBy,
            ]);
            $bankTxId = $bankTx->id;
        }

        if ($pettyCashAccountId) {
            $account = PettyCashAccount::where('id', $pettyCashAccountId)
                ->where('company_id', $companyId)
                ->firstOrFail();

            $pettyCashTx = PettyCashTransaction::create([
                'company_id'       => $companyId,
                'account_id'       => $account->id,
                'type'             => 'top_up',
                'amount'           => (float) $amount,
                'currency'         => $currency,
                'description'      => $description,
                'transaction_date' => $date,
                'ref_type'         => $refType,
                'ref_id'           => $refId,
                'created_by'       => $createdBy,
            ]);
            $pettyCashTxId = $pettyCashTx->id;
        }

        return [
            'bank_transaction_id'       => $bankTxId,
            'petty_cash_transaction_id' => $pettyCashTxId,
        ];
    }

    /**
     * Load all active bank accounts + petty cash accounts for the given company.
     * Convenience method for controllers to pass to views.
     */
    public static function accountsForCompany(int $companyId): array
    {
        return [
            'bankAccounts' => BankAccount::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'pettyCashAccounts' => PettyCashAccount::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ];
    }
}
