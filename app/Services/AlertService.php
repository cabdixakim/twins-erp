<?php

namespace App\Services;

use App\Models\ImportTruck;
use App\Models\Document;
use Illuminate\Support\Collection;

class AlertService
{
    public static function getForCompany(int $companyId): array
    {
        $alerts = [];

        // ── Trucks in_transit for > 3 days ───────────────────────────────────
        ImportTruck::where('status', 'in_transit')
            ->whereNotNull('in_transit_at')
            ->where('in_transit_at', '<', now()->subDays(3))
            ->whereHas('nomination.purchase', fn($q) => $q->where('company_id', $companyId))
            ->with(['nomination.purchase'])
            ->get()
            ->each(function ($truck) use (&$alerts) {
                $days = (int) now()->diffInDays($truck->in_transit_at);
                $ref  = $truck->nomination?->purchase?->reference ?? '';
                $alerts[] = [
                    'type'     => 'warning',
                    'category' => 'overdue_transit',
                    'icon'     => 'truck',
                    'title'    => "{$truck->truck_reg} overdue in transit",
                    'body'     => "In transit for {$days} days" . ($ref ? " · {$ref}" : ''),
                    'link'     => $truck->nomination?->purchase
                                    ? route('purchases.show', $truck->nomination->purchase)
                                    : null,
                    'age'      => $truck->in_transit_at,
                ];
            });

        // ── Trucks at_border for > 2 days ─────────────────────────────────────
        ImportTruck::where('status', 'at_border')
            ->whereNotNull('arrived_at_border_at')
            ->where('arrived_at_border_at', '<', now()->subDays(2))
            ->whereHas('nomination.purchase', fn($q) => $q->where('company_id', $companyId))
            ->with(['nomination.purchase'])
            ->get()
            ->each(function ($truck) use (&$alerts) {
                $days = (int) now()->diffInDays($truck->arrived_at_border_at);
                $ref  = $truck->nomination?->purchase?->reference ?? '';
                $alerts[] = [
                    'type'     => 'warning',
                    'category' => 'overdue_border',
                    'icon'     => 'border',
                    'title'    => "{$truck->truck_reg} stuck at border",
                    'body'     => "Waiting {$days} days" . ($ref ? " · {$ref}" : ''),
                    'link'     => $truck->nomination?->purchase
                                    ? route('purchases.show', $truck->nomination->purchase)
                                    : null,
                    'age'      => $truck->arrived_at_border_at,
                ];
            });

        // ── Trucks border_cleared with pending duty ───────────────────────────
        ImportTruck::where('duty_status', 'pending')
            ->whereNotNull('duty_amount')
            ->where('duty_amount', '>', 0)
            ->whereHas('nomination.purchase', fn($q) => $q->where('company_id', $companyId))
            ->with(['nomination.purchase'])
            ->get()
            ->each(function ($truck) use (&$alerts) {
                $ref    = $truck->nomination?->purchase?->reference ?? '';
                $amount = number_format((float) $truck->duty_amount, 2);
                $alerts[] = [
                    'type'     => 'info',
                    'category' => 'pending_duty',
                    'icon'     => 'duty',
                    'title'    => "{$truck->truck_reg} — duty unpaid",
                    'body'     => "{$truck->duty_currency} {$amount} pending" . ($ref ? " · {$ref}" : ''),
                    'link'     => $truck->nomination?->purchase
                                    ? route('purchases.show', $truck->nomination->purchase)
                                    : null,
                    'age'      => $truck->border_cleared_at ?? $truck->updated_at,
                ];
            });

        // ── Expired company documents ─────────────────────────────────────────
        Document::where('company_id', $companyId)
            ->whereIn('category', Document::$categories)
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now()->toDateString())
            ->orderBy('valid_until')
            ->get()
            ->each(function ($doc) use (&$alerts) {
                $daysAgo = (int) now()->diffInDays($doc->valid_until);
                $alerts[] = [
                    'type'     => 'warning',
                    'category' => 'doc_expired',
                    'icon'     => 'document',
                    'title'    => "{$doc->category_label} expired",
                    'body'     => "{$doc->name} — expired {$daysAgo} day" . ($daysAgo === 1 ? '' : 's') . " ago",
                    'link'     => route('documents.index'),
                    'age'      => $doc->valid_until,
                ];
            });

        // ── Documents expiring within 30 days ────────────────────────────────
        Document::where('company_id', $companyId)
            ->whereIn('category', Document::$categories)
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '>=', now()->toDateString())
            ->whereDate('valid_until', '<=', now()->addDays(30)->toDateString())
            ->orderBy('valid_until')
            ->get()
            ->each(function ($doc) use (&$alerts) {
                $days = (int) now()->diffInDays($doc->valid_until);
                $alerts[] = [
                    'type'     => 'info',
                    'category' => 'doc_expiring',
                    'icon'     => 'document',
                    'title'    => "{$doc->category_label} expiring soon",
                    'body'     => "{$doc->name} — " . ($days === 0 ? 'expires today' : "expires in {$days} day" . ($days === 1 ? '' : 's')),
                    'link'     => route('documents.index'),
                    'age'      => $doc->valid_until,
                ];
            });

        // Sort: warnings first, then by age (most urgent / oldest date first)
        usort($alerts, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'warning' ? -1 : 1;
            }
            return strcmp((string) ($a['age'] ?? ''), (string) ($b['age'] ?? ''));
        });

        return $alerts;
    }

    public static function countForCompany(int $companyId): int
    {
        return count(self::getForCompany($companyId));
    }
}
