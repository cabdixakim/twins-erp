@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $chargeTypeLabels = [
        'storage_charge'   => ['label' => 'Storage',    'color' => 'bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30'],
        'handling_fee'     => ['label' => 'Handling fee', 'color' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30'],
        'loading_fee'      => ['label' => 'Loading fee','color' => 'bg-orange-500/15 text-orange-700 dark:text-orange-300 border border-orange-500/30'],
        'duty_charge'      => ['label' => 'Duty',       'color' => 'bg-violet-500/15 text-violet-700 dark:text-violet-300 border border-violet-500/30'],
        'other_charge'     => ['label' => 'Other',      'color' => 'bg-slate-500/15 text-slate-600 dark:text-slate-300 border border-slate-500/30'],
        'payment'          => ['label' => 'Payment',    'color' => 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-500/30'],
        'adjustment'       => ['label' => 'Adjustment', 'color' => 'bg-slate-500/15 text-slate-600 dark:text-slate-300 border border-slate-500/30'],
    ];

    $sym = fn(string $code) => match($code) {
        'USD' => '$', 'EUR' => '€', 'GBP' => '£',
        'ZAR' => 'R ', 'CDF' => 'FC ', 'ZMW' => 'K ', 'ZWL' => 'ZWL ',
        default => $code . ' '
    };
@endphp

@extends('layouts.app')
@section('title', $depot->name . ' — Charges')
@section('subtitle', 'Storage, throughput & loading fees')

@section('content')

@if(session('status'))
    <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-600 text-white px-4 py-2.5 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif

{{-- Back + actions --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-2">
    <a href="{{ route('depots.index') }}"
       class="inline-flex items-center gap-1.5 text-xs {{ $muted }} hover:text-[color:var(--tw-fg)] transition">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        All depots
    </a>
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('depots.statement', $depot) }}" target="_blank"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/>
            </svg>
            Print statement
        </a>
        <a href="{{ route('depots.export', $depot) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
            </svg>
            Export CSV
        </a>
        <button type="button" onclick="document.getElementById('chargeModal').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Record charge
        </button>
        <button type="button" onclick="document.getElementById('paymentModal').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Record payment
        </button>
    </div>
</div>

{{-- Depot name --}}
<div class="mb-5">
    <h1 class="text-xl font-bold {{ $fg }} mb-0.5">{{ $depot->name }}</h1>
    <p class="text-xs {{ $muted }}">
        {{ $depot->city ?: '' }}
        @if($depot->contact_person) · {{ $depot->contact_person }} @endif
        @if($depot->default_currency) · {{ $depot->default_currency }} @endif
    </p>
</div>

{{-- Balance summary --}}
<div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Total charges</div>
        <div class="text-base font-bold {{ $fg }}">{{ $sym($currency) }}{{ number_format($chargesTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Storage, throughput, loading</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Payments made</div>
        <div class="text-base font-bold text-sky-500">{{ $sym($currency) }}{{ number_format($paymentTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Settled charges</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Net payable</div>
        @if(abs($netPayable) < 0.005)
            <div class="text-base font-bold text-emerald-500">Settled</div>
        @elseif($netPayable > 0)
            <div class="text-base font-bold text-amber-500">{{ $sym($currency) }}{{ number_format($netPayable, 2) }}</div>
        @else
            <div class="text-base font-bold text-emerald-500">Overpaid {{ $sym($currency) }}{{ number_format(abs($netPayable), 2) }}</div>
        @endif
        <div class="text-[10px] {{ $muted }}">Current balance owed</div>
    </div>
</div>

{{-- Charge rate configurations --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden mb-6">
    <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between gap-2 flex-wrap">
        <div>
            <span class="text-xs font-semibold {{ $fg }}">Charge rate configurations</span>
            <span class="ml-2 text-[10px] {{ $muted }}">Auto-posted at truck delivery · storage accrues monthly</span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="document.getElementById('monthlyStorageModal').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl border border-purple-500/30 bg-purple-500/10 text-[11px] font-semibold text-purple-600 dark:text-purple-400 hover:bg-purple-500/20 transition">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                </svg>
                Post monthly storage
            </button>
            <button type="button" onclick="document.getElementById('addConfigModal').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl border {{ $border }} {{ $surface }} text-[11px] font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add rate
            </button>
        </div>
    </div>

    @if($chargeConfigs->isEmpty())
        <div class="px-5 py-6 text-center">
            <p class="text-xs {{ $muted }}">No charge rates configured — charges won't auto-post at delivery.</p>
            <p class="text-xs {{ $muted }} mt-1">Add storage, offloading, duty or customs rates to automate landed cost capture.</p>
        </div>
    @else
        @php
            $catColors = [
                'storage'    => 'bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30',
                'offloading' => 'bg-orange-500/15 text-orange-700 dark:text-orange-300 border border-orange-500/30',
                'duty'       => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30',
                'customs'    => 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-500/30',
                'other'      => 'bg-slate-500/15 text-slate-600 dark:text-slate-300 border border-slate-500/30',
            ];
            $sym = fn(string $code) => match($code) {
                'USD' => '$', 'EUR' => '€', 'GBP' => '£',
                'ZAR' => 'R ', 'CDF' => 'FC ', 'ZMW' => 'K ',
                default => $code . ' '
            };
        @endphp
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs {{ $muted }} border-b {{ $border }}">
                        <th class="text-left py-2.5 pl-5 pr-3 font-semibold">Name</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Category</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Rate</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Billing rule</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Paid by</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Effective</th>
                        <th class="text-left py-2.5 pr-5 font-semibold">Status</th>
                        <th class="py-2.5 pr-5"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chargeConfigs as $cfg)
                    <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors {{ $cfg->is_active ? '' : 'opacity-50' }}">
                        <td class="py-3 pl-5 pr-3 text-xs font-semibold {{ $fg }}">{{ $cfg->name }}</td>
                        <td class="py-3 pr-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $catColors[$cfg->category] ?? $catColors['other'] }}">
                                {{ \App\Models\DepotChargeConfig::categoryLabel($cfg->category) }}
                            </span>
                        </td>
                        <td class="py-3 pr-3 text-xs {{ $fg }} whitespace-nowrap">
                            {{ $sym($cfg->currency) }}{{ number_format((float)$cfg->rate, 4) }}
                            <span class="text-[10px] {{ $muted }}">{{ \App\Models\DepotChargeConfig::rateUnitLabel($cfg->rate_unit) }}</span>
                        </td>
                        <td class="py-3 pr-3 text-[11px] {{ $muted }}">
                            @if($cfg->category === 'storage' && $cfg->receipt_rule)
                                {{ \App\Models\DepotChargeConfig::receiptRuleLabel($cfg->receipt_rule) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="py-3 pr-3 text-[11px] {{ $muted }}">
                            {{ \App\Models\DepotChargeConfig::paidByLabel($cfg->paid_by_type, $cfg->paid_by_name) }}
                        </td>
                        <td class="py-3 pr-3 text-[11px] {{ $muted }} whitespace-nowrap">
                            {{ $cfg->effective_from->format('d M Y') }}
                            @if($cfg->effective_to) – {{ $cfg->effective_to->format('d M Y') }} @else onwards @endif
                        </td>
                        <td class="py-3 pr-3">
                            @if($cfg->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">Active</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/10 text-slate-500 border border-slate-500/20">Inactive</span>
                            @endif
                        </td>
                        <td class="py-3 pr-5">
                            <div class="flex items-center gap-2 justify-end">
                                <form method="POST" action="{{ route('depots.charge-configs.toggle', [$depot, $cfg]) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="text-[11px] {{ $muted }} hover:text-[color:var(--tw-fg)] transition underline underline-offset-2">
                                        {{ $cfg->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('depots.charge-configs.destroy', [$depot, $cfg]) }}"
                                      onsubmit="return confirm('Delete this charge config?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-[11px] text-rose-500 hover:text-rose-400 transition underline underline-offset-2">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Ledger entries --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden mb-6">
    <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
        <span class="text-xs font-semibold {{ $fg }}">Charge & payment entries</span>
        <span class="text-xs {{ $muted }}">Most recent first</span>
    </div>

    @if($entries->isEmpty())
        <div class="p-8 text-center">
            <p class="text-sm {{ $muted }}">No entries yet — use "Record charge" to add depot charges.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs {{ $muted }} border-b {{ $border }}">
                        <th class="text-left py-2.5 pl-5 pr-3 font-semibold">Date</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Type</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Description</th>
                        <th class="text-right py-2.5 pr-3 font-semibold">Charge</th>
                        <th class="text-right py-2.5 pr-5 font-semibold">Payment</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $e)
                        @php
                            $meta     = $chargeTypeLabels[$e->type] ?? ['label' => $e->type, 'color' => 'bg-slate-500/15 text-slate-400 border border-slate-500/30'];
                            $isCharge = (float) $e->amount > 0;
                        @endphp
                        <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors">
                            <td class="py-3 pl-5 pr-3 text-xs {{ $muted }} whitespace-nowrap">
                                {{ $e->entry_date->format('d M Y') }}
                            </td>
                            <td class="py-3 pr-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $meta['color'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="py-3 pr-3 text-xs {{ $fg }}">{{ $e->description }}</td>
                            <td class="py-3 pr-3 text-right text-xs font-semibold {{ $isCharge ? 'text-amber-500' : $muted }}">
                                {{ $isCharge ? ($sym($e->currency) . number_format(abs((float)$e->amount), 2)) : '' }}
                            </td>
                            <td class="py-3 pr-5 text-right text-xs font-semibold {{ !$isCharge ? 'text-sky-400' : $muted }}">
                                {{ !$isCharge ? ($sym($e->currency) . number_format(abs((float)$e->amount), 2)) : '' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="px-5 py-3 border-t {{ $border }}">
                {{ $entries->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Add Charge Config Modal --}}
<div id="addConfigModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
     style="background:rgba(0,0,0,.55)" onclick="document.getElementById('addConfigModal').classList.add('hidden')">
    <div class="w-full max-w-lg rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl p-6 max-h-[90vh] overflow-y-auto"
         onclick="event.stopPropagation()">
        <h3 class="text-sm font-bold {{ $fg }} mb-4">Add charge rate — {{ $depot->name }}</h3>
        <form method="POST" action="{{ route('depots.charge-configs.store', $depot) }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="text-xs font-semibold {{ $muted }}">Name / label</label>
                    <input name="name" required maxlength="200"
                           class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
                           placeholder="e.g. Storage rate 2026, Offloading fee">
                </div>
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">Category</label>
                    <select name="category" required id="cfg_category"
                            class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
                            onchange="toggleStorageRules()">
                        <option value="storage">Storage</option>
                        <option value="offloading">Offloading</option>
                        <option value="duty">Duty</option>
                        <option value="customs">Customs</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">Rate unit</label>
                    <select name="rate_unit" required
                            class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
                        <option value="per_m3_per_month">Per m³ / month (storage)</option>
                        <option value="per_m3">Per m³ (one-off)</option>
                        <option value="per_trip">Per trip (flat)</option>
                        <option value="lump_sum">Lump sum / month</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">Rate</label>
                    <input name="rate" type="number" step="0.000001" min="0" required
                           class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
                           placeholder="0.00">
                </div>
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">Currency</label>
                    <input name="currency" value="{{ $depot->default_currency ?: 'USD' }}" maxlength="8" required
                           class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
                </div>
            </div>

            {{-- Storage billing rules (shown only for storage category) --}}
            <div id="storageRulesBlock" class="rounded-xl border {{ $border }} p-3 bg-purple-500/5 space-y-2">
                <div class="text-[11px] font-semibold text-purple-600 dark:text-purple-400 mb-1">Storage billing rules</div>
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">Receipt month rule</label>
                    <select name="receipt_rule"
                            class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
                        <option value="include_receipt_month">Charge from receipt month (post at delivery)</option>
                        <option value="prorate_receipt_month">Prorate receipt month (days remaining / days in month)</option>
                        <option value="exclude_receipt_month">Skip receipt month (charge starts next month)</option>
                        <option value="exclude_first_30_days">Exclude first 30 days (charge starts day 31)</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">Dispatch month rule</label>
                    <select name="dispatch_rule"
                            class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
                        <option value="include_dispatch_month">Charge for dispatch month</option>
                        <option value="exclude_dispatch_month">Skip dispatch month</option>
                    </select>
                </div>
            </div>

            {{-- Who pays --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">Payable to</label>
                    <select name="paid_by_type"
                            class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
                        <option value="self">We pay directly (COGS only, no secondary AP)</option>
                        <option value="depot">This depot (COGS + depot ledger payable)</option>
                        <option value="customs_authority">Customs authority (COGS only, tracked by name)</option>
                        <option value="transporter">Transporter (COGS + transporter advance)</option>
                        <option value="exempt">Exempt — contractually waived (nothing posted)</option>
                        <option value="other">Other third party (COGS only)</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">Party name (if other)</label>
                    <input name="paid_by_name" maxlength="200"
                           class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
                           placeholder="e.g. DGRAD, customs agent">
                </div>
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">Effective from</label>
                    <input name="effective_from" type="date" value="{{ now()->toDateString() }}" required
                           class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
                </div>
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">Effective to (leave blank = open-ended)</label>
                    <input name="effective_to" type="date"
                           class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Notes (optional)</label>
                <input name="notes" maxlength="1000"
                       class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
                       placeholder="e.g. DRC govt rate as of Jan 2026">
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="button" onclick="document.getElementById('addConfigModal').classList.add('hidden')"
                        class="flex-1 h-9 rounded-xl border {{ $border }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 h-9 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
                    Save rate config
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleStorageRules() {
    const cat = document.getElementById('cfg_category').value;
    const block = document.getElementById('storageRulesBlock');
    block.style.display = cat === 'storage' ? '' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleStorageRules);
</script>

{{-- Monthly Storage Modal --}}
<div id="monthlyStorageModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
     style="background:rgba(0,0,0,.55)" onclick="document.getElementById('monthlyStorageModal').classList.add('hidden')">
    <div class="w-full max-w-lg rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl p-6"
         onclick="event.stopPropagation()">
        <h3 class="text-sm font-bold {{ $fg }} mb-1">Post monthly storage — {{ $depot->name }}</h3>
        <p class="text-xs {{ $muted }} mb-4">Posts storage charges for all batches currently held at this depot, based on closing balance (qty on hand).</p>

        {{-- Period selector --}}
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-1">
                <label class="text-xs font-semibold {{ $muted }}">Month</label>
                <select id="storageMonth" onchange="loadStoragePreview()"
                        class="mt-1 w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-purple-500/40">
                    @foreach(range(1,12) as $m)
                        <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="text-xs font-semibold {{ $muted }}">Year</label>
                <select id="storageYear" onchange="loadStoragePreview()"
                        class="mt-1 w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-purple-500/40">
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mt-5">
                <button type="button" onclick="loadStoragePreview()"
                        class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Preview
                </button>
            </div>
        </div>

        {{-- Preview area --}}
        <div id="storagePreviewArea" class="mb-4 min-h-[80px] rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $muted }}">
            <span id="storagePreviewPlaceholder">Select a period and click Preview to see what will be charged.</span>
            <div id="storagePreviewTable" class="hidden"></div>
        </div>

        {{-- Hidden form for actual post --}}
        <form id="monthlyStorageForm" method="POST" action="{{ route('depots.monthly-storage.run', $depot) }}">
            @csrf
            <input type="hidden" name="year"  id="storageFormYear"  value="{{ now()->year }}">
            <input type="hidden" name="month" id="storageFormMonth" value="{{ now()->month }}">
            <div class="flex items-center gap-3">
                <button type="button" onclick="document.getElementById('monthlyStorageModal').classList.add('hidden')"
                        class="flex-1 h-9 rounded-xl border {{ $border }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Cancel
                </button>
                <button type="submit" id="storagePostBtn"
                        class="flex-1 h-9 rounded-xl border border-purple-500/40 bg-purple-500/10 text-xs font-semibold text-purple-600 dark:text-purple-400 hover:bg-purple-500/20 transition">
                    Post charges
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function loadStoragePreview() {
    const month = document.getElementById('storageMonth').value;
    const year  = document.getElementById('storageYear').value;
    document.getElementById('storageFormYear').value  = year;
    document.getElementById('storageFormMonth').value = month;

    const area  = document.getElementById('storagePreviewArea');
    const table = document.getElementById('storagePreviewTable');
    const ph    = document.getElementById('storagePreviewPlaceholder');

    ph.textContent = 'Loading…';
    ph.classList.remove('hidden');
    table.classList.add('hidden');
    table.innerHTML = '';

    fetch(`{{ route('depots.monthly-storage.preview', $depot) }}?year=${year}&month=${month}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        ph.classList.add('hidden');
        table.classList.remove('hidden');

        if (!data.has_configs) {
            table.innerHTML = '<p class="text-amber-500 text-xs">No active storage configs for this depot.</p>';
            return;
        }
        if (!data.rows || data.rows.length === 0) {
            table.innerHTML = '<p class="text-xs">No stock held at this depot — nothing to charge.</p>';
            return;
        }

        let html = '<table class="w-full text-xs"><thead><tr class="border-b border-[color:var(--tw-border)]">'
            + '<th class="text-left py-1.5 pr-3 font-semibold">Product</th>'
            + '<th class="text-left py-1.5 pr-3 font-semibold">Config</th>'
            + '<th class="text-right py-1.5 pr-3 font-semibold">m³</th>'
            + '<th class="text-right py-1.5 font-semibold">Amount</th>'
            + '</tr></thead><tbody>';

        let anyNew = false;
        data.rows.forEach(r => {
            const statusCls = r.already_posted
                ? 'text-slate-400 line-through'
                : r.chargeable ? 'font-semibold text-[color:var(--tw-fg)]' : 'text-slate-400 italic';
            const statusTag = r.already_posted
                ? '<span class="ml-1 text-[9px] bg-slate-500/15 px-1.5 py-0.5 rounded-full">posted</span>'
                : !r.chargeable ? '<span class="ml-1 text-[9px] bg-amber-500/15 text-amber-600 px-1.5 py-0.5 rounded-full">deferred</span>' : '';
            const amtStr = r.amount > 0
                ? `${r.currency} ${r.amount.toFixed(2)}`
                : (r.already_posted ? 'done' : '—');
            if (r.chargeable && !r.already_posted) anyNew = true;
            html += `<tr class="border-b border-[color:var(--tw-border)] last:border-0">
                <td class="py-1.5 pr-3 ${statusCls}">${r.product}${statusTag}</td>
                <td class="py-1.5 pr-3 text-[color:var(--tw-muted)]">${r.config_name}</td>
                <td class="py-1.5 pr-3 text-right text-[color:var(--tw-muted)]">${Number(r.qty_m3).toFixed(3)}</td>
                <td class="py-1.5 text-right ${r.chargeable && !r.already_posted ? 'text-purple-600 dark:text-purple-400 font-semibold' : 'text-[color:var(--tw-muted)]'}">${amtStr}</td>
            </tr>`;
        });
        html += '</tbody>';

        if (data.totals && Object.keys(data.totals).length > 0) {
            html += '<tfoot class="border-t border-[color:var(--tw-border)]"><tr>';
            html += '<td colspan="3" class="pt-2 font-semibold text-[color:var(--tw-fg)] text-xs">Total to post</td>';
            const totStr = Object.entries(data.totals).map(([c,v]) => `${c} ${v.toFixed(2)}`).join(' + ');
            html += `<td class="pt-2 text-right font-bold text-purple-600 dark:text-purple-400 text-xs">${totStr}</td>`;
            html += '</tr></tfoot>';
        }

        html += '</table>';
        table.innerHTML = html;

        document.getElementById('storagePostBtn').disabled = !anyNew;
        document.getElementById('storagePostBtn').style.opacity = anyNew ? '' : '0.4';
    })
    .catch(() => {
        ph.classList.remove('hidden');
        ph.textContent = 'Preview failed — check that the depot has active storage configs.';
    });
}
</script>

{{-- Record Charge Modal --}}
<div id="chargeModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
     style="background:rgba(0,0,0,.55)">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl p-6"
         onclick="event.stopPropagation()">
        <h3 class="text-sm font-bold {{ $fg }} mb-4">Record charge from {{ $depot->name }}</h3>
        <form method="POST" action="{{ route('depots.charges.store', $depot) }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Charge type</label>
                <select name="type" required
                        class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
                    <option value="storage_charge">Storage charge</option>
                    <option value="handling_fee">Handling fee</option>
                    <option value="loading_fee">Loading fee</option>
                    <option value="duty_charge">Duty charge</option>
                    <option value="other_charge">Other charge</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Amount</label>
                <div class="flex gap-2 mt-1">
                    <input name="amount" type="number" step="0.01" min="0.01" required
                           class="flex-1 rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
                           placeholder="0.00">
                    <input name="currency" value="{{ $depot->default_currency ?: 'USD' }}"
                           class="w-20 rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
                           maxlength="8">
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Date</label>
                <input name="entry_date" type="date" value="{{ now()->toDateString() }}" required
                       class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
            </div>
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Description (optional)</label>
                <input name="description" type="text"
                       class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
                       placeholder="e.g. March 2026 storage fee">
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="button" onclick="document.getElementById('chargeModal').classList.add('hidden')"
                        class="flex-1 h-9 rounded-xl border {{ $border }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 h-9 rounded-xl border border-amber-500/40 bg-amber-500/10 text-xs font-semibold text-amber-500 hover:bg-amber-500/20 transition">
                    Save charge
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Record Payment Modal --}}
<div id="paymentModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
     style="background:rgba(0,0,0,.55)">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl p-6"
         onclick="event.stopPropagation()">
        <h3 class="text-sm font-bold {{ $fg }} mb-4">Record payment to {{ $depot->name }}</h3>
        <form method="POST" action="{{ route('depots.payments.store', $depot) }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Amount</label>
                <div class="flex gap-2 mt-1">
                    <input name="amount" type="number" step="0.01" min="0.01" required
                           class="flex-1 rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
                           placeholder="0.00">
                    <input name="currency" value="{{ $depot->default_currency ?: 'USD' }}"
                           class="w-20 rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
                           maxlength="8">
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Date</label>
                <input name="entry_date" type="date" value="{{ now()->toDateString() }}" required
                       class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
            </div>
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Note (optional)</label>
                <input name="description" type="text"
                       class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
                       placeholder="e.g. Bank transfer ref 12345">
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')"
                        class="flex-1 h-9 rounded-xl border {{ $border }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 h-9 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
                    Save payment
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
