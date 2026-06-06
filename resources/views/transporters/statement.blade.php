@php
    $sym = match($currency) {
        'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'ZAR' => 'R ',
        'CDF' => 'FC ', 'ZMW' => 'K ', 'ZWL' => 'ZWL ', default => $currency . ' '
    };
    $typeMeta = [
        'freight_charge' => ['label' => 'Freight',      'badge' => '#dcfce7', 'text' => '#166534'],
        'advance'        => ['label' => 'Advance',      'badge' => '#fef9c3', 'text' => '#854d0e'],
        'short_charge'   => ['label' => 'Short charge', 'badge' => '#fee2e2', 'text' => '#991b1b'],
        'payment'        => ['label' => 'Payment',      'badge' => '#dbeafe', 'text' => '#1e40af'],
        'recovery'       => ['label' => 'Recovery',     'badge' => '#f3e8ff', 'text' => '#6b21a8'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Statement — {{ $transporter->name }}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;font-size:12px;color:#1a1a2e;background:#eef2f7;padding:28px 16px}
.controls{display:flex;justify-content:center;gap:10px;margin-bottom:24px}
.btn{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;border:none;transition:opacity .15s}
.btn-dark{background:#1e293b;color:#fff}
.btn-light{background:#fff;color:#334155;border:1px solid #e2e8f0}
.btn:hover{opacity:.88}
.page{max-width:820px;margin:0 auto;background:#fff;border-radius:14px;box-shadow:0 4px 32px rgba(0,0,0,.10);overflow:hidden}

/* ── Header ── */
.hdr{background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%);color:#fff;padding:30px 36px;display:flex;justify-content:space-between;align-items:flex-start}
.co-name{font-size:22px;font-weight:800;letter-spacing:-.5px}
.co-sub{font-size:11px;opacity:.55;margin-top:3px;letter-spacing:.3px}
.stmt-title .lbl{font-size:9px;text-transform:uppercase;letter-spacing:2px;opacity:.5}
.stmt-title h1{font-size:21px;font-weight:700;margin-top:2px}
.stmt-title .gen{font-size:11px;opacity:.6;margin-top:3px}

/* ── Info row ── */
.info-row{display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid #e2e8f0}
.info-block{padding:18px 36px;border-right:1px solid #e2e8f0}
.info-block:last-child{border-right:none}
.info-block .lbl{font-size:9px;text-transform:uppercase;letter-spacing:1.5px;color:#94a3b8;margin-bottom:5px}
.info-block .val{font-size:14px;font-weight:700;color:#1e293b}
.info-block .sub{font-size:11px;color:#64748b;margin-top:2px}

/* ── Summary bar ── */
.summary{display:grid;grid-template-columns:repeat(5,1fr);background:#f8fafc;border-bottom:1px solid #e2e8f0}
.sum-item{padding:14px 16px;border-right:1px solid #e2e8f0}
.sum-item:last-child{border-right:none}
.sum-item .lbl{font-size:9px;text-transform:uppercase;letter-spacing:.8px;color:#94a3b8;margin-bottom:5px}
.sum-item .amt{font-size:15px;font-weight:800}
.sum-item .sub{font-size:10px;color:#94a3b8;margin-top:2px}

/* ── Table ── */
.tbl-hdr{padding:16px 36px 12px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between}
.tbl-hdr h2{font-size:13px;font-weight:700;color:#334155}
.tbl-hdr .cnt{font-size:11px;color:#94a3b8}
table{width:100%;border-collapse:collapse}
thead tr{background:#f8fafc;border-bottom:2px solid #e2e8f0}
th{padding:9px 10px;text-align:left;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.9px;color:#64748b}
th.r{text-align:right}
th:first-child{padding-left:36px}
th:last-child{padding-right:36px}
tbody tr{border-bottom:1px solid #f1f5f9}
tbody tr:nth-child(even){background:#fafbfd}
td{padding:8px 10px;font-size:11px;color:#334155;vertical-align:middle}
td:first-child{padding-left:36px}
td:last-child{padding-right:36px;text-align:right;font-weight:600}
td.r{text-align:right;font-weight:600}
td.mt{color:#94a3b8}
.badge{display:inline-block;padding:2px 8px;border-radius:100px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.4px}
.col-dr{color:#1e293b}
.col-cr{color:#dc2626}
.col-ow{color:#d97706;font-weight:700}
.col-ok{color:#16a34a;font-weight:700}
tr.total td{font-weight:800;font-size:12px;background:#f8fafc;border-top:2px solid #cbd5e1;padding-top:13px;padding-bottom:13px}

/* ── Footer ── */
.foot{padding:18px 36px;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;background:#f8fafc}
.foot .l{font-size:10px;color:#94a3b8}
.foot .l strong{color:#475569;font-size:11px}
.foot .r{font-size:10px;color:#94a3b8;text-align:right}

/* ── Print ── */
@media print{
  body{background:#fff;padding:0}
  .controls{display:none}
  .page{max-width:none;box-shadow:none;border-radius:0}
  tbody tr{page-break-inside:avoid}
  @page{size:A4;margin:1.2cm 1.5cm}
}
</style>
</head>
<body>

<div class="controls">
    <a href="{{ route('transporters.show', $transporter) }}" class="btn btn-light">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back
    </a>
    <a href="{{ route('transporters.export', $transporter) }}" class="btn btn-light">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
        Export CSV
    </a>
    <button onclick="window.print()" class="btn btn-dark">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
        Print / Save as PDF
    </button>
</div>

<div class="page">

    {{-- Header --}}
    <div class="hdr">
        <div>
            <div class="co-name">{{ $company->name ?? 'TWINS ERP' }}</div>
            <div class="co-sub">Fuel &amp; Transport Management</div>
        </div>
        <div class="stmt-title" style="text-align:right">
            <div class="lbl">Document</div>
            <h1>Transporter Statement</h1>
            <div class="gen">Generated {{ now()->format('d M Y') }}</div>
        </div>
    </div>

    {{-- Info row --}}
    <div class="info-row">
        <div class="info-block">
            <div class="lbl">Transporter</div>
            <div class="val">{{ $transporter->name }}</div>
            <div class="sub">
                {{ $transporter->type === 'intl' ? 'International transporter' : 'Local transporter' }}
                @if($transporter->country) &middot; {{ $transporter->country }}@endif
            </div>
            @if($transporter->contact_person)
                <div class="sub">{{ $transporter->contact_person }}@if($transporter->phone) &middot; {{ $transporter->phone }}@endif</div>
            @endif
        </div>
        <div class="info-block">
            <div class="lbl">Statement Period</div>
            @if($entries->isNotEmpty())
                <div class="val">{{ $entries->first()->entry_date->format('d M Y') }} — {{ $entries->last()->entry_date->format('d M Y') }}</div>
            @else
                <div class="val" style="color:#94a3b8">No transactions recorded</div>
            @endif
            <div class="sub">Currency: {{ $currency }} &nbsp;&middot;&nbsp; {{ $entries->count() }} {{ $entries->count() === 1 ? 'transaction' : 'transactions' }}</div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="summary">
        <div class="sum-item">
            <div class="lbl">Freight earned</div>
            <div class="amt" style="color:#16a34a">{{ $sym }}{{ number_format($freightTotal,2) }}</div>
            <div class="sub">Gross from deliveries</div>
        </div>
        <div class="sum-item">
            <div class="lbl">Advances paid</div>
            <div class="amt" style="color:#d97706">{{ $sym }}{{ number_format($advanceTotal,2) }}</div>
            <div class="sub">Upfront payments</div>
        </div>
        <div class="sum-item">
            <div class="lbl">Short charges</div>
            <div class="amt" style="color:#dc2626">{{ $sym }}{{ number_format($shortChargeTotal,2) }}</div>
            <div class="sub">Deducted for excess loss</div>
        </div>
        <div class="sum-item">
            <div class="lbl">Payments made</div>
            <div class="amt" style="color:#2563eb">{{ $sym }}{{ number_format($paymentTotal,2) }}</div>
            <div class="sub">Settled invoices</div>
        </div>
        <div class="sum-item" style="{{ $netPayable > 0.005 ? 'background:#fffbeb' : 'background:#f0fdf4' }}">
            <div class="lbl">Net payable</div>
            @if(abs($netPayable) < 0.005)
                <div class="amt col-ok">Settled</div>
                <div class="sub">Nothing outstanding</div>
            @elseif($netPayable > 0)
                <div class="amt col-ow">{{ $sym }}{{ number_format($netPayable,2) }}</div>
                <div class="sub">Still owed to transporter</div>
            @else
                <div class="amt col-ok">{{ $sym }}{{ number_format(abs($netPayable),2) }} CR</div>
                <div class="sub">Credit on account</div>
            @endif
        </div>
    </div>

    {{-- Table header --}}
    <div class="tbl-hdr">
        <h2>Transaction Ledger</h2>
        <span class="cnt">{{ $entries->count() }} {{ $entries->count() === 1 ? 'entry' : 'entries' }}</span>
    </div>

    @if($entries->isEmpty())
        <div style="padding:48px 36px;text-align:center;color:#94a3b8;font-size:13px">
            No transactions recorded yet.
        </div>
    @else
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th class="r">Debit (owed)</th>
                <th class="r">Credit (paid)</th>
                <th class="r">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                @php
                    $isDebit = $entry->amount > 0;
                    $meta = $typeMeta[$entry->type] ?? ['label' => ucfirst(str_replace('_',' ',$entry->type)), 'badge' => '#f1f5f9', 'text' => '#475569'];
                    $bal = $entry->running_balance;
                @endphp
                <tr>
                    <td class="mt" style="white-space:nowrap">{{ $entry->entry_date->format('d M Y') }}</td>
                    <td>
                        <span class="badge" style="background:{{ $meta['badge'] }};color:{{ $meta['text'] }}">{{ $meta['label'] }}</span>
                    </td>
                    <td style="max-width:240px">{{ $entry->description }}</td>
                    <td class="r col-dr">
                        @if($isDebit){{ $sym }}{{ number_format($entry->amount,2) }}@else<span style="color:#cbd5e1">—</span>@endif
                    </td>
                    <td class="r col-cr">
                        @if(!$isDebit){{ $sym }}{{ number_format(abs($entry->amount),2) }}@else<span style="color:#cbd5e1">—</span>@endif
                    </td>
                    <td class="r {{ $bal > 0.005 ? 'col-ow' : ($bal < -0.005 ? 'col-ok' : 'col-ok') }}">
                        @if(abs($bal) < 0.005)
                            <span style="color:#16a34a">Nil</span>
                        @elseif($bal > 0)
                            {{ $sym }}{{ number_format($bal,2) }}
                        @else
                            {{ $sym }}{{ number_format(abs($bal),2) }}&nbsp;CR
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="3">Closing Balance</td>
                <td class="r col-dr">{{ $sym }}{{ number_format($freightTotal,2) }}</td>
                <td class="r col-cr">{{ $sym }}{{ number_format($advanceTotal + $shortChargeTotal + $paymentTotal,2) }}</td>
                <td class="r {{ $netPayable > 0.005 ? 'col-ow' : 'col-ok' }}">
                    @if(abs($netPayable) < 0.005)
                        Settled
                    @elseif($netPayable > 0)
                        {{ $sym }}{{ number_format($netPayable,2) }}
                    @else
                        {{ $sym }}{{ number_format(abs($netPayable),2) }}&nbsp;CR
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- Footer --}}
    <div class="foot">
        <div class="l">
            <strong>TWINS ERP</strong><br>
            Fuel &amp; Transport Management System
        </div>
        <div class="r">
            Generated {{ now()->format('d M Y, H:i') }}<br>
            Confidential — for internal use only
        </div>
    </div>

</div>

</body>
</html>
