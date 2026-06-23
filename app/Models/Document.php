<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $guarded = [];

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
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'tr8'      => 'TR8',
            't1'       => 'T1',
            'customs'  => 'Customs',
            'invoice'  => 'Invoice',
            'permit'   => 'Permit',
            'contract' => 'Contract',
            default    => 'Other',
        };
    }

    public function delete(): bool|null
    {
        Storage::disk('local')->delete($this->file_path);
        return parent::delete();
    }

    public static $categories = ['tr8', 't1', 'customs', 'invoice', 'permit', 'contract', 'other'];
}
