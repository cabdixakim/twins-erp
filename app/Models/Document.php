<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $guarded = [];

    protected $casts = [
        'valid_from'  => 'date',
        'valid_until' => 'date',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024)    return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'trade_license'          => 'Trade Licence',
            'insurance_certificate'  => 'Insurance',
            'tax_clearance'          => 'Tax Clearance',
            'company_registration'   => 'Registration',
            'contract'               => 'Contract',
            'certificate'            => 'Certificate',
            'company_profile'        => 'Company Profile',
            'permit'                 => 'Permit',
            // legacy border-doc categories (internal)
            'tr8'                    => 'TR8',
            't1'                     => 'T1',
            'customs'                => 'Customs',
            'invoice'                => 'Invoice',
            default                  => 'Other',
        };
    }

    /**
     * 'expired' | 'expiring_soon' (≤30 days) | 'valid' | 'no_expiry'
     */
    public function getExpiryStatusAttribute(): string
    {
        if (! $this->valid_until) return 'no_expiry';
        $days = now()->startOfDay()->diffInDays($this->valid_until->startOfDay(), false);
        if ($days < 0)  return 'expired';
        if ($days <= 30) return 'expiring_soon';
        return 'valid';
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->valid_until) return null;
        return (int) now()->startOfDay()->diffInDays($this->valid_until->startOfDay(), false);
    }

    public function delete(): bool|null
    {
        Storage::disk('local')->delete($this->file_path);
        return parent::delete();
    }

    // Company vault categories shown in the UI
    public static array $categories = [
        'trade_license',
        'insurance_certificate',
        'tax_clearance',
        'company_registration',
        'contract',
        'certificate',
        'company_profile',
        'permit',
        'other',
    ];

    // All categories (includes internal border-doc ones)
    public static array $allCategories = [
        'trade_license', 'insurance_certificate', 'tax_clearance',
        'company_registration', 'contract', 'certificate',
        'company_profile', 'permit', 'other',
        'tr8', 't1', 'customs', 'invoice',
    ];
}
