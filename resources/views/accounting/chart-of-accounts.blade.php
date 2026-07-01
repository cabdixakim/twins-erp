@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('subtitle', 'Manage the account structure used for double-entry journals.')

@section('content')

<div class="space-y-5">

    {{-- Breadcrumb --}}
    <div class="no-print flex items-center gap-2 text-xs mb-1" style="color:var(--tw-muted)">
        <a href="{{ route('accounting.index') }}" class="hover:underline">Accounting</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span>Chart of Accounts</span>
    </div>

    {{-- Actions bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3">

        {{-- Filter --}}
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search accounts…"
                   class="rounded-xl border px-3 py-1.5 text-sm w-52"
                   style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
            <select name="type" class="rounded-xl border px-3 py-1.5 text-sm"
                    style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
                <option value="">All types</option>
                @foreach(['asset','liability','equity','revenue','expense'] as $t)
                <option value="{{ $t }}" @selected($type===$t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
            <button type="submit" class="tw-btn-primary text-xs px-3 py-1.5 rounded-xl">Filter</button>
        </form>

        <div class="flex items-center gap-2">
            @if(!$hasAccounts)
            <form method="POST" action="{{ route('accounting.coa.seed') }}">@csrf
                <button type="submit" class="tw-btn-primary text-xs px-4 py-2 rounded-xl">Seed Standard Accounts</button>
            </form>
            @endif

            <button type="button" onclick="document.getElementById('addAccountModal').classList.remove('hidden')"
                    class="tw-btn-primary text-xs px-4 py-2 rounded-xl flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Account
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="text-sm text-emerald-400 bg-emerald-400/10 border border-emerald-400/20 rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('info'))
    <div class="text-sm text-amber-400 bg-amber-400/10 border border-amber-400/20 rounded-xl px-4 py-3">{{ session('info') }}</div>
    @endif

    {{-- Table --}}
    @if($accounts->isEmpty())
    <div class="rounded-2xl border p-12 text-center" style="background:var(--tw-surface);border-color:var(--tw-border)">
        <p class="text-sm mb-3" style="color:var(--tw-muted)">No accounts yet. Seed the standard chart or add accounts manually.</p>
        <form method="POST" action="{{ route('accounting.coa.seed') }}" class="inline">@csrf
            <button type="submit" class="tw-btn-primary text-xs px-5 py-2 rounded-xl">Seed Standard Chart of Accounts</button>
        </form>
    </div>
    @else
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-[11px] uppercase tracking-wider" style="background:var(--tw-surface-2);color:var(--tw-muted)">
                    <th class="px-4 py-3 text-left">Code</th>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Sub-type</th>
                    <th class="px-4 py-3 text-left">Parent</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @foreach($accounts as $acct)
                <tr class="hover:bg-white/[.02] transition" style="background:var(--tw-surface)">
                    <td class="px-4 py-3 font-mono text-xs font-semibold" style="color:var(--tw-fg)">{{ $acct->code }}</td>
                    <td class="px-4 py-3 font-medium" style="color:var(--tw-fg)">{{ $acct->name }}</td>
                    <td class="px-4 py-3">
                        @php $typeColors = ['asset'=>'text-sky-400','liability'=>'text-rose-400','equity'=>'text-purple-400','revenue'=>'text-emerald-400','expense'=>'text-amber-400'] @endphp
                        <span class="text-[11px] font-semibold {{ $typeColors[$acct->type] ?? '' }}">{{ ucfirst($acct->type) }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs" style="color:var(--tw-muted)">{{ $acct->sub_type ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs" style="color:var(--tw-muted)">{{ $acct->parent?->code ? $acct->parent->code.' '.$acct->parent->name : '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="text-[10px] font-semibold {{ $acct->is_active ? 'text-emerald-400' : 'text-rose-400' }}">{{ $acct->is_active ? 'Active' : 'Inactive' }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if(!$acct->is_system)
                        <form method="POST" action="{{ route('accounting.coa.destroy', $acct) }}" onsubmit="return confirm('Delete account {{ $acct->code }}?')" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 transition">Delete</button>
                        </form>
                        @else
                        <span class="text-[10px]" style="color:var(--tw-muted)">System</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Add Account Modal --}}
<div id="addAccountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60">
    <div class="rounded-2xl border shadow-2xl w-full max-w-md p-6" style="background:var(--tw-surface);border-color:var(--tw-border)">
        <h2 class="text-sm font-bold mb-4" style="color:var(--tw-fg)">Add Account</h2>
        <form method="POST" action="{{ route('accounting.coa.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">Code *</label>
                    <input type="text" name="code" required maxlength="32"
                           class="w-full rounded-xl border px-3 py-2 text-sm"
                           style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">Type *</label>
                    <select name="type" required class="w-full rounded-xl border px-3 py-2 text-sm"
                            style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
                        @foreach(['asset','liability','equity','revenue','expense'] as $t)
                        <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">Name *</label>
                <input type="text" name="name" required maxlength="200"
                       class="w-full rounded-xl border px-3 py-2 text-sm"
                       style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">Sub-type</label>
                    <input type="text" name="sub_type" maxlength="40" placeholder="e.g. current_asset"
                           class="w-full rounded-xl border px-3 py-2 text-sm"
                           style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">Parent Account</label>
                    <select name="parent_id" class="w-full rounded-xl border px-3 py-2 text-sm"
                            style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
                        <option value="">None</option>
                        @foreach($accounts as $a)
                        <option value="{{ $a->id }}">{{ $a->code }} {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @error('code')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('addAccountModal').classList.add('hidden')"
                        class="text-xs rounded-xl border px-4 py-2 hover:bg-white/5 transition"
                        style="border-color:var(--tw-border);color:var(--tw-muted)">Cancel</button>
                <button type="submit" class="tw-btn-primary text-xs px-5 py-2 rounded-xl">Add Account</button>
            </div>
        </form>
    </div>
</div>

@endsection
