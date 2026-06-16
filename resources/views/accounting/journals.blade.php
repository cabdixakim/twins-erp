@extends('layouts.app')
@section('title', 'Journal Entries')
@section('subtitle', 'Post and review double-entry journal entries.')

@section('content')

<div class="space-y-5">

    {{-- Alerts --}}
    @if(session('success'))
    <div class="text-sm text-emerald-400 bg-emerald-400/10 border border-emerald-400/20 rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if($errors->has('lines'))
    <div class="text-sm text-rose-400 bg-rose-400/10 border border-rose-400/20 rounded-xl px-4 py-3">{{ $errors->first('lines') }}</div>
    @endif

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <div>
                <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">From</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="rounded-xl border px-3 py-1.5 text-sm"
                       style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
            </div>
            <div>
                <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">To</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="rounded-xl border px-3 py-1.5 text-sm"
                       style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
            </div>
            <select name="status" class="rounded-xl border px-3 py-1.5 text-sm"
                    style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
                <option value="">All statuses</option>
                <option value="draft" @selected($status==='draft')>Draft</option>
                <option value="posted" @selected($status==='posted')>Posted</option>
                <option value="reversed" @selected($status==='reversed')>Reversed</option>
            </select>
            <button type="submit" class="tw-btn-primary text-xs px-3 py-1.5 rounded-xl">Filter</button>
        </form>

        <button type="button" onclick="document.getElementById('newJournalModal').classList.remove('hidden')"
                class="tw-btn-primary text-xs px-4 py-2 rounded-xl flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Entry
        </button>
    </div>

    {{-- Entries table --}}
    @if($entries->isEmpty())
    <div class="rounded-2xl border p-12 text-center" style="background:var(--tw-surface);border-color:var(--tw-border)">
        <p class="text-sm" style="color:var(--tw-muted)">No journal entries found. Post your first entry using the button above.</p>
    </div>
    @else
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-[11px] uppercase tracking-wider" style="background:var(--tw-surface-2);color:var(--tw-muted)">
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Reference</th>
                    <th class="px-4 py-3 text-left">Description</th>
                    <th class="px-4 py-3 text-right">Debit</th>
                    <th class="px-4 py-3 text-right">Credit</th>
                    <th class="px-4 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @foreach($entries as $entry)
                <tr class="hover:bg-white/[.02] transition" style="background:var(--tw-surface)">
                    <td class="px-4 py-3 text-xs" style="color:var(--tw-muted)">{{ $entry->entry_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-mono text-xs font-semibold" style="color:var(--tw-fg)">{{ $entry->reference }}</td>
                    <td class="px-4 py-3 text-xs" style="color:var(--tw-fg)">{{ Str::limit($entry->description,60) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums font-semibold" style="color:var(--tw-fg)">{{ number_format($entry->lines->sum('debit'),2) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums font-semibold" style="color:var(--tw-fg)">{{ number_format($entry->lines->sum('credit'),2) }}</td>
                    <td class="px-4 py-3 text-center">
                        @php $sc = ['posted'=>'text-emerald-400','draft'=>'text-amber-400','reversed'=>'text-rose-400'] @endphp
                        <span class="text-[10px] font-semibold {{ $sc[$entry->status] ?? '' }}">{{ ucfirst($entry->status) }}</span>
                    </td>
                </tr>
                {{-- Lines sub-rows --}}
                @foreach($entry->lines as $line)
                <tr style="background:var(--tw-surface-2)">
                    <td class="px-4 py-1.5 pl-8 text-[11px]" style="color:var(--tw-muted)"></td>
                    <td class="px-4 py-1.5 text-[11px] font-mono" style="color:var(--tw-muted)">{{ $line->account?->code }}</td>
                    <td class="px-4 py-1.5 text-[11px]" style="color:var(--tw-muted)">{{ $line->account?->name }}{{ $line->description ? ' — '.$line->description : '' }}</td>
                    <td class="px-4 py-1.5 text-right text-[11px] tabular-nums" style="color:var(--tw-muted)">{{ $line->debit > 0 ? number_format($line->debit,2) : '' }}</td>
                    <td class="px-4 py-1.5 text-right text-[11px] tabular-nums" style="color:var(--tw-muted)">{{ $line->credit > 0 ? number_format($line->credit,2) : '' }}</td>
                    <td></td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $entries->links() }}</div>
    @endif
</div>

{{-- New Journal Entry Modal --}}
<div id="newJournalModal" class="hidden fixed inset-0 z-50 flex items-start justify-center pt-16 bg-black/60 overflow-y-auto">
    <div class="rounded-2xl border shadow-2xl w-full max-w-2xl p-6 mb-16" style="background:var(--tw-surface);border-color:var(--tw-border)">
        <h2 class="text-sm font-bold mb-4" style="color:var(--tw-fg)">New Journal Entry</h2>
        <form method="POST" action="{{ route('accounting.journals.store') }}" class="space-y-4" id="journalForm">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">Journal *</label>
                    <select name="journal_id" required class="w-full rounded-xl border px-3 py-2 text-sm"
                            style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
                        @forelse($journals as $j)
                        <option value="{{ $j->id }}">{{ $j->name }}</option>
                        @empty
                        <option value="">No journals — seed accounts first</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">Date *</label>
                    <input type="date" name="entry_date" value="{{ today()->toDateString() }}" required
                           class="w-full rounded-xl border px-3 py-2 text-sm"
                           style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">Reference *</label>
                <input type="text" name="reference" required maxlength="80" placeholder="e.g. JE-2026-001"
                       class="w-full rounded-xl border px-3 py-2 text-sm"
                       style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
            </div>
            <div>
                <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">Description *</label>
                <input type="text" name="description" required maxlength="500"
                       class="w-full rounded-xl border px-3 py-2 text-sm"
                       style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
            </div>

            {{-- Lines --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-[11px] font-semibold" style="color:var(--tw-muted)">Lines (min 2, debits must equal credits)</label>
                    <button type="button" id="addLine" class="text-xs text-emerald-400 hover:text-emerald-300 transition">+ Add line</button>
                </div>
                <div id="linesContainer" class="space-y-2">
                    @for($i=0;$i<2;$i++)
                    <div class="grid grid-cols-12 gap-2 items-start line-row">
                        <div class="col-span-5">
                            <select name="lines[{{ $i }}][account_id]" required
                                    class="w-full rounded-xl border px-2 py-1.5 text-xs"
                                    style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
                                <option value="">— Account —</option>
                                @foreach($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->code }} {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-3">
                            <input type="number" name="lines[{{ $i }}][debit]" step="0.01" min="0" value="0" placeholder="Debit"
                                   class="w-full rounded-xl border px-2 py-1.5 text-xs text-right"
                                   style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
                        </div>
                        <div class="col-span-3">
                            <input type="number" name="lines[{{ $i }}][credit]" step="0.01" min="0" value="0" placeholder="Credit"
                                   class="w-full rounded-xl border px-2 py-1.5 text-xs text-right"
                                   style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
                        </div>
                        <div class="col-span-1 flex items-center justify-center pt-1">
                            <button type="button" class="remove-line text-rose-400 hover:text-rose-300 text-xs hidden">✕</button>
                        </div>
                    </div>
                    @endfor
                </div>
                <div class="flex justify-between text-[11px] mt-2 px-1" style="color:var(--tw-muted)">
                    <span>Debit total: <span id="debitTotal" class="font-semibold text-emerald-400">0.00</span></span>
                    <span>Credit total: <span id="creditTotal" class="font-semibold text-emerald-400">0.00</span></span>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('newJournalModal').classList.add('hidden')"
                        class="text-xs rounded-xl border px-4 py-2 hover:bg-white/5 transition"
                        style="border-color:var(--tw-border);color:var(--tw-muted)">Cancel</button>
                <button type="submit" class="tw-btn-primary text-xs px-5 py-2 rounded-xl">Post Entry</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function(){
    let lineCount = 2;
    const accountOptions = `{!! $accounts->map(fn($a)=>'<option value="'.$a->id.'">'.$a->code.' '.$a->name.'</option>')->implode('') !!}`;

    function updateTotals(){
        let d=0,c=0;
        document.querySelectorAll('.line-row').forEach(row=>{
            d += parseFloat(row.querySelector('[name*="[debit]"]').value)||0;
            c += parseFloat(row.querySelector('[name*="[credit]"]').value)||0;
        });
        document.getElementById('debitTotal').textContent = d.toFixed(2);
        document.getElementById('creditTotal').textContent = c.toFixed(2);
        document.getElementById('debitTotal').style.color = Math.abs(d-c)<0.01?'#10b981':'#ef4444';
        document.getElementById('creditTotal').style.color = Math.abs(d-c)<0.01?'#10b981':'#ef4444';
    }

    document.getElementById('linesContainer').addEventListener('input', updateTotals);

    document.getElementById('addLine').addEventListener('click', ()=>{
        const idx = lineCount++;
        const div = document.createElement('div');
        div.className = 'grid grid-cols-12 gap-2 items-start line-row';
        div.innerHTML = `
            <div class="col-span-5"><select name="lines[${idx}][account_id]" required class="w-full rounded-xl border px-2 py-1.5 text-xs" style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)"><option value="">— Account —</option>${accountOptions}</select></div>
            <div class="col-span-3"><input type="number" name="lines[${idx}][debit]" step="0.01" min="0" value="0" class="w-full rounded-xl border px-2 py-1.5 text-xs text-right" style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)"></div>
            <div class="col-span-3"><input type="number" name="lines[${idx}][credit]" step="0.01" min="0" value="0" class="w-full rounded-xl border px-2 py-1.5 text-xs text-right" style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)"></div>
            <div class="col-span-1 flex items-center justify-center pt-1"><button type="button" class="remove-line text-rose-400 hover:text-rose-300 text-xs">✕</button></div>`;
        document.getElementById('linesContainer').appendChild(div);
        div.querySelector('.remove-line').addEventListener('click', ()=>{ div.remove(); updateTotals(); });
        div.addEventListener('input', updateTotals);
    });

    document.querySelectorAll('.remove-line').forEach(btn=>{
        btn.addEventListener('click', ()=>{ btn.closest('.line-row').remove(); updateTotals(); });
    });
})();
</script>
@endpush

@endsection
