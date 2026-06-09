<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_at'  => 'datetime',
        'before_data' => 'array',
        'after_data'  => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an audit event.
     *
     * @param  string       $event       created|updated|deleted|posted|voided|paid|confirmed|received|cancelled|issued|adjusted|dispatched|transferred|nominated|login|logout
     * @param  string       $description Human-readable summary of what happened.
     * @param  mixed        $model       Eloquent model being acted on (optional).
     * @param  string       $modelLabel  Friendly label, e.g. "Purchase PO-TWN-2026-00001".
     * @param  int|null     $companyId   Override company (defaults to user's active company).
     * @param  string       $severity    info | warning | critical
     * @param  array        $before      Key-value snapshot BEFORE the change.
     * @param  array        $after       Key-value snapshot AFTER the change.
     * @param  string|null  $module      Module name: Purchase | Sale | Invoice | Client | Supplier | Depot | Transport
     */
    public static function record(
        string  $event,
        string  $description,
        mixed   $model       = null,
        string  $modelLabel  = '',
        ?int    $companyId   = null,
        string  $severity    = 'info',
        array   $before      = [],
        array   $after       = [],
        ?string $module      = null,
    ): void {
        try {
            $user    = auth()->user();
            $request = request();

            // Auto-detect module from model class if not supplied
            if ($module === null && $model !== null) {
                $module = self::moduleFromClass(get_class($model));
            }

            self::create([
                'company_id'  => $companyId ?? $user?->active_company_id,
                'user_id'     => $user?->id,
                'user_name'   => $user?->name,
                'event'       => $event,
                'severity'    => $severity,
                'module'      => $module,
                'model_type'  => $model ? get_class($model) : null,
                'model_id'    => $model?->id,
                'model_label' => $modelLabel ?: ($model
                    ? class_basename(get_class($model)) . ' #' . $model->id
                    : null),
                'description' => $description,
                'ip_address'  => $request?->ip(),
                'url'         => $request ? substr($request->fullUrl(), 0, 500) : null,
                'method'      => $request?->method(),
                'user_agent'  => $request ? substr((string) $request->userAgent(), 0, 500) : null,
                'before_data' => $before ?: null,
                'after_data'  => $after ?: null,
                'created_at'  => now(),
            ]);
        } catch (\Throwable) {
            // Never let audit logging break the actual operation
        }
    }

    private static function moduleFromClass(string $fqcn): string
    {
        return match (class_basename($fqcn)) {
            'Purchase'            => 'Purchase',
            'Sale'                => 'Sale',
            'Invoice'             => 'Invoice',
            'Client'              => 'Client',
            'Supplier'            => 'Supplier',
            'Transporter'         => 'Transport',
            'ImportNomination',
            'ImportTruck'         => 'Transport',
            'Depot'               => 'Depot',
            'DepotLedgerEntry'    => 'Depot',
            'SupplierLedgerEntry' => 'Supplier',
            'ClientLedgerEntry'   => 'Client',
            'User'                => 'Admin',
            'Role'                => 'Admin',
            default               => class_basename($fqcn),
        };
    }
}
