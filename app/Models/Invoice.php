<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'subtotal'         => 'float',
        'tax_rate'         => 'float',
        'tax_amount'       => 'float',
        'discount_amount'  => 'float',
        'total'            => 'float',
        'paid_amount'      => 'float',
        'issued_date'      => 'date',
        'due_date'         => 'date',
        'paid_at'          => 'datetime',
    ];

    public function client(): BelongsTo  { return $this->belongsTo(Client::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function sale(): BelongsTo    { return $this->belongsTo(Sale::class); }
    public function items(): HasMany     { return $this->hasMany(InvoiceItem::class)->orderBy('sort_order'); }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->paid_amount);
    }

    public function getDaysOverdueAttribute(): int
    {
        if (in_array($this->status, ['paid', 'void'], true)) return 0;
        $days = (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);
        return $days < 0 ? abs($days) : 0;
    }

    public function getAgingBucketAttribute(): string
    {
        $overdue = $this->days_overdue;
        if ($overdue === 0)   return 'current';
        if ($overdue <= 30)   return '1-30';
        if ($overdue <= 60)   return '31-60';
        if ($overdue <= 90)   return '61-90';
        return '90+';
    }

    // ── Static factory: create invoice from a posted sale ────────────────────

    public static function createFromSale(Sale $sale, Company $company): self
    {
        return DB::transaction(function () use ($sale, $company) {
            $seq = (int) self::where('company_id', $company->id)
                ->max('sequence_no') + 1;

            $prefix      = $company->invoice_prefix ?: 'INV';
            $code        = $company->code ? "-{$company->code}" : '';
            $year        = now()->year;
            $number      = "{$prefix}{$code}-{$year}-" . str_pad($seq, 5, '0', STR_PAD_LEFT);

            $taxRate     = (float) ($company->invoice_tax_rate ?? 0);
            $subtotal    = (float) $sale->total;
            $taxAmount   = round($subtotal * $taxRate / 100, 2);
            $total       = round($subtotal + $taxAmount, 2);
            $paymentDays = (int) ($company->invoice_payment_days ?? 30);

            $issuedDate  = $sale->sale_date
                ? Carbon::parse($sale->sale_date)
                : now();

            $invoice = self::create([
                'company_id'      => $company->id,
                'client_id'       => $sale->client_id,
                'sale_id'         => $sale->id,
                'invoice_number'  => $number,
                'sequence_no'     => $seq,
                'status'          => 'sent',
                'currency'        => $sale->currency ?? 'USD',
                'subtotal'        => $subtotal,
                'tax_rate'        => $taxRate,
                'tax_amount'      => $taxAmount,
                'discount_amount' => 0,
                'total'           => $total,
                'paid_amount'     => 0,
                'issued_date'     => $issuedDate,
                'due_date'        => $issuedDate->copy()->addDays($paymentDays),
                'footer_text'     => $company->invoice_footer_notes,
                'bank_details'    => $company->invoice_bank_details,
                'payment_terms'   => "Net {$paymentDays} days",
                'created_by'      => auth()->id(),
                'updated_by'      => auth()->id(),
            ]);

            $productName = $sale->product?->name ?? 'Fuel';
            $qtyFmt      = number_format((float) $sale->qty, 0, '.', ',');
            $volumeUnit  = $company->volume_unit ?: 'L';

            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'sort_order'  => 1,
                'description' => "{$productName} — {$qtyFmt} {$volumeUnit}",
                'qty'         => (float) $sale->qty,
                'unit_price'  => (float) $sale->unit_price,
                'amount'      => $subtotal,
            ]);

            return $invoice;
        });
    }
}
