<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveCompany;

class ImportNomination extends Model
{
    use BelongsToActiveCompany;

    protected $table = 'import_nominations';

    protected $fillable = [
        'company_id',
        'purchase_id',
        'transporter_id',
        'currency',
        'rate_per_1000l',
        'allowed_loss_pct',
        'short_charge_rate',
        'short_charge_currency',
        'advances',
        'advances_currency',
        'notes',
        'status',
        'created_by',
        'volume_unit',
        'default_duty_vendor_type',
        'default_duty_vendor_id',
        'default_duty_rate_per_1000l',
        'default_duty_currency',
    ];

    // Note: hospitality_rate / hospitality_currency removed — storage fee
    // is a depot charge config, not a per-nomination field.

    protected $casts = [
        'rate_per_1000l'    => 'decimal:4',
        'allowed_loss_pct'  => 'decimal:4',
        'short_charge_rate' => 'decimal:4',
        'advances'          => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function trucks()
    {
        return $this->hasMany(ImportTruck::class, 'nomination_id')->orderBy('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Computed helpers ─────────────────────────────────────────────────────

    public function qtyCapacity(): float
    {
        return (float) $this->trucks->sum('capacity');
    }

    public function qtyLoaded(): float
    {
        return (float) $this->trucks
            ->whereNotIn('status', ['nominated', 'loading_failed'])
            ->sum('qty_loaded');
    }

    public function qtyDelivered(): float
    {
        return (float) $this->trucks->where('status', 'delivered')->sum('qty_delivered');
    }

    public function totalShortfallCharge(): float
    {
        return (float) $this->trucks->where('status', 'delivered')->sum('shortfall_charge');
    }

    public function grossPayable(): float
    {
        // Rate is per unit (per L when volume_unit=L, per M³ when M3) — no hidden divisor
        return $this->qtyLoaded() * (float) $this->rate_per_1000l;
    }

    public function netPayable(): float
    {
        return $this->grossPayable() - (float) $this->advances - $this->totalShortfallCharge();
    }
}
