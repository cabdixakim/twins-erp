<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJobRow extends Model
{
    protected $guarded = [];

    protected $casts = [
        'raw_data'    => 'array',
        'mapped_data' => 'array',
        'errors'      => 'array',
        'row_number'  => 'integer',
        'result_id'   => 'integer',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class, 'job_id');
    }
}
