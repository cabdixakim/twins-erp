<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an audit event. Call this from controllers after significant actions.
     */
    public static function record(
        string $event,
        string $description,
        mixed  $model       = null,
        string $modelLabel  = '',
        ?int   $companyId   = null,
    ): void {
        try {
            $user = auth()->user();

            self::create([
                'company_id'  => $companyId ?? $user?->active_company_id,
                'user_id'     => $user?->id,
                'user_name'   => $user?->name,
                'event'       => $event,
                'model_type'  => $model ? get_class($model) : null,
                'model_id'    => $model?->id,
                'model_label' => $modelLabel ?: ($model
                    ? class_basename(get_class($model)) . ' #' . $model->id
                    : null),
                'description' => $description,
                'ip_address'  => request()->ip(),
            ]);
        } catch (\Throwable) {
            // Never let audit logging break the actual operation
        }
    }
}
