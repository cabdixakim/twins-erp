@extends('layouts.app')
@section('title', 'Accounting')
@section('subtitle', 'Chart of accounts, journals, P&L and balance sheet.')

@section('content')

<div class="max-w-5xl mx-auto space-y-6">

    {{-- MTD P&L strip --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="rounded-2xl border p-4 flex sm:block items-center justify-between gap-3"
             style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--tw-muted)">Revenue (MTD)</div>
            <div class="text-xl font-bold sm:mt-1" style="color:var(--tw-fg)">{{ number_format($summary['revenue_mtd'],2) }}</div>
        </div>
        <div class="rounded-2xl border p-4 flex sm:block items-center justify-between gap-3"
             style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--tw-muted)">COGS (MTD)</div>
            <div class="text-xl font-bold sm:mt-1" style="color:var(--tw-fg)">{{ number_format($summary['cogs_mtd'],2) }}</div>
        </div>
        <div class="rounded-2xl border p-4 flex sm:block items-center justify-between gap-3"
             style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--tw-muted)">Gross Profit (MTD)</div>
            <div class="text-xl font-bold sm:mt-1 {{ $summary['gross_profit'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                {{ number_format($summary['gross_profit'],2) }}
            </div>
        </div>
    </div>

    {{-- Module cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

        {{-- Chart of Accounts --}}
        <a href="{{ route('accounting.coa') }}"
           class="group rounded-2xl border p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block"
           style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
                 style="background:rgba(16,185,129,.10);border:1px solid rgba(16,185,129,.20)">
                <svg class="w-6 h-6" style="color:#10b981" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold mb-1 group-hover:text-emerald-400 transition" style="color:var(--tw-fg)">Chart of Accounts</h3>
            <p class="text-xs leading-relaxed" style="color:var(--tw-muted)">Define your account structure — assets, liabilities, equity, revenue and expenses.</p>
            @if($summary['coa_count'] > 0)
            <div class="mt-4 text-[10px] font-semibold text-emerald-400">{{ $summary['coa_count'] }} accounts</div>
            @else
            <div class="mt-4 text-[10px] font-semibold text-amber-400">Not set up — seed standard accounts</div>
            @endif
        </a>

        {{-- P&L --}}
        <a href="{{ route('accounting.pl') }}"
           class="group rounded-2xl border p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block"
           style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
                 style="background:rgba(99,102,241,.10);border:1px solid rgba(99,102,241,.20)">
                <svg class="w-6 h-6" style="color:#6366f1" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold mb-1 group-hover:text-indigo-400 transition" style="color:var(--tw-fg)">Profit & Loss</h3>
            <p class="text-xs leading-relaxed" style="color:var(--tw-muted)">Revenue, COGS, gross and net profit for any date range — derived from operational data.</p>
        </a>

        {{-- Balance Sheet --}}
        <a href="{{ route('accounting.balance-sheet') }}"
           class="group rounded-2xl border p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block"
           style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
                 style="background:rgba(14,165,233,.10);border:1px solid rgba(14,165,233,.20)">
                <svg class="w-6 h-6" style="color:#0ea5e9" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.97zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.97z"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold mb-1 group-hover:text-sky-400 transition" style="color:var(--tw-fg)">Balance Sheet</h3>
            <p class="text-xs leading-relaxed" style="color:var(--tw-muted)">Assets vs. liabilities snapshot — bank, stock, AR, supplier payables, equity position.</p>
        </a>

        {{-- Journals --}}
        <a href="{{ route('accounting.journals') }}"
           class="group rounded-2xl border p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block"
           style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
                 style="background:rgba(245,158,11,.10);border:1px solid rgba(245,158,11,.20)">
                <svg class="w-6 h-6" style="color:#f59e0b" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold mb-1 group-hover:text-amber-400 transition" style="color:var(--tw-fg)">Journal Entries</h3>
            <p class="text-xs leading-relaxed" style="color:var(--tw-muted)">Post manual double-entry journal entries and view the complete ledger.</p>
            @if($summary['draft_count'] > 0)
            <div class="mt-4 text-[10px] font-semibold text-amber-400">{{ $summary['draft_count'] }} drafts pending</div>
            @elseif($summary['journal_count'] > 0)
            <div class="mt-4 text-[10px] font-semibold" style="color:var(--tw-muted)">{{ $summary['journal_count'] }} posted entries</div>
            @endif
        </a>

        {{-- General Ledger --}}
        <a href="{{ route('accounting.ledger') }}"
           class="group rounded-2xl border p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block"
           style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
                 style="background:rgba(20,184,166,.10);border:1px solid rgba(20,184,166,.20)">
                <svg class="w-6 h-6" style="color:#14b8a6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold mb-1 group-hover:text-teal-400 transition" style="color:var(--tw-fg)">General Ledger</h3>
            <p class="text-xs leading-relaxed" style="color:var(--tw-muted)">Per-account transaction history with running balance — drill into any account to see every debit and credit posted.</p>
        </a>

        {{-- Trial Balance --}}
        <a href="{{ route('accounting.trial-balance') }}"
           class="group rounded-2xl border p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block"
           style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
                 style="background:rgba(168,85,247,.10);border:1px solid rgba(168,85,247,.20)">
                <svg class="w-6 h-6" style="color:#a855f7" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold mb-1 group-hover:text-purple-400 transition" style="color:var(--tw-fg)">Trial Balance</h3>
            <p class="text-xs leading-relaxed" style="color:var(--tw-muted)">Debit vs. credit totals by account for any period — verify books balance.</p>
        </a>

        {{-- AP Aging link --}}
        <a href="{{ route('reports.ap-aging') }}"
           class="group rounded-2xl border p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block"
           style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
                 style="background:rgba(239,68,68,.10);border:1px solid rgba(239,68,68,.20)">
                <svg class="w-6 h-6" style="color:#ef4444" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold mb-1 group-hover:text-rose-400 transition" style="color:var(--tw-fg)">AP Aging</h3>
            <p class="text-xs leading-relaxed" style="color:var(--tw-muted)">Outstanding supplier and transporter payables bucketed by age.</p>
        </a>

    </div>
</div>

@endsection
