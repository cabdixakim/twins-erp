@php
    $sym = match($currency) {
        'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'ZAR' => 'R ',
        'CDF' => 'FC ', 'ZMW' => 'K ', 'ZWL' => 'ZWL ', default => $currency . ' '
    };
    $typeMeta = [
        'freight_charge' => ['label' => 'Freight',      'badge' => '#d1fae5', 'text' => '#065f46'],
        'advance'        => ['label' => 'Advance',      'badge' => '#fef3c7', 'text' => '#92400e'],
        'short_charge'   => ['label' => 'Short charge', 'badge' => '#fee2e2', 'text' => '#991b1b'],
        'payment'        => ['label' => 'Payment',      'badge' => '#dbeafe', 'text' => '#1e40af'],
        'recovery'       => ['label' => 'Recovery',     'badge' => '#ede9fe', 'text' => '#5b21b6'],
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
body{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;font-size:12px;color:#111827;background:#e5e7eb;padding:32px 16px;-webkit-print-color-adjust:exact;print-color-adjust:exact}
a{text-decoration:none}

/* ── Screen controls ── */
.controls{display:flex;justify-content:center;gap:10px;margin-bottom:28px}
.btn{display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;border:none;transition:opacity .15s;line-height:1}
.btn-dark{background:#111827;color:#fff}
.btn-light{background:#fff;color:#374151;border:1px solid #d1d5db;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.btn:hover{opacity:.85}

/* ── Page ── */
.page{max-width:840px;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.12);overflow:hidden}

/* ── Header band ── */
.hdr{background:#0f172a;color:#fff;padding:28px 40px 24px;display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.hdr-left{}
.co-name{font-size:20px;font-weight:800;letter-spacing:-.4px;color:#f8fafc}
.co-tagline{font-size:10.5px;color:#94a3b8;margin-top:3px;letter-spacing:.2px}
.hdr-right{text-align:right;flex-shrink:0}
.doc-label{font-size:9px;text-transform:uppercase;letter-spacing:2.5px;color:#64748b;margin-bottom:4px}
.doc-title{font-size:18px;font-weight:800;color:#f8fafc;letter-spacing:-.3px}
.doc-date{font-size:11px;color:#64748b;margin-top:5px}

/* ── Accent stripe (thin color bar under header) ── */
.accent-stripe{height:4px;background:linear-gradient(90deg,#0ea5e9 0%,#6366f1 50%,#10b981 100%)}

/* ── Info row ── */
.info-row{display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid #e5e7eb}
.info-block{padding:20px 40px}
.info-block+.info-block{border-left:1px solid #e5e7eb}
.ib-label{font-size:9px;text-transform:uppercase;letter-spacing:1.8px;color:#9ca3af;margin-bottom:7px;font-weight:600}
.ib-name{font-size:16px;font-weight:700;color:#111827;line-height:1.2}
.ib-sub{font-size:11px;color:#6b7280;margin-top:3px;line-height:1.5}

/* ── Summary strip ── */
.summary-strip{display:grid;grid-template-columns:repeat(4,1fr);background:#f8fafc;border-bottom:1px solid #e5e7eb}
.sum-cell{padding:16px 20px;position:relative}
.sum-cell+.sum-cell{border-left:1px solid #e5e7eb}
.sum-cell .lbl{font-size:9px;text-transform:uppercase;letter-spacing:1.2px;color:#9ca3af;font-weight:600;margin-bottom:6px}
.sum-cell .amt{font-size:18px;font-weight:800;line-height:1}
.sum-cell .sub{font-size:10px;color:#9ca3af;margin-top:4px}
.sum-charges .amt{color:#059669}
.sum-payments .amt{color:#2563eb}
.sum-deductions .amt{color:#dc2626}
.sum-balance-owed .amt{color:#d97706}
.sum-balance-clear .amt{color:#059669}

/* ── Balance highlight box ── */
.balance-highlight{margin:16px 40px;border-radius:12px;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px}
.bh-owed{background:#fffbeb;border:1.5px solid #fbbf24}
.bh-clear{background:#f0fdf4;border:1.5px solid #86efac}
.bh-label{font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.8px}
.bh-value{font-size:22px;font-weight:800}
.bh-owed .bh-value{color:#d97706}
.bh-clear .bh-value{color:#059669}
.bh-note{font-size:11px;color:#9ca3af;margin-top:2px}

/* ── Section title ── */
.section-title{padding:14px 40px 10px;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;background:#f9fafb;display:flex;align-items:center;justify-content:space-between}
.section-title h2{font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.8px}
.section-title .cnt{font-size:11px;color:#9ca3af}

/* ── Table ── */
table{width:100%;border-collapse:collapse}
thead tr{background:#f9fafb}
th{padding:10px 12px;text-align:left;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#6b7280;border-bottom:2px solid #e5e7eb;white-space:nowrap}
th:first-child{padding-left:40px}
th:last-child{padding-right:40px;text-align:right}
th.r{text-align:right}
tbody tr{border-bottom:1px solid #f3f4f6;transition:background .1s}
tbody tr:nth-child(even){background:#fafafa}
tbody tr:last-child{border-bottom:none}
td{padding:9px 12px;font-size:11.5px;color:#374151;vertical-align:middle}
td:first-child{padding-left:40px}
td:last-child{padding-right:40px;text-align:right;font-weight:600}
td.r{text-align:right;font-weight:600}
td.muted{color:#9ca3af;font-size:11px}
.badge{display:inline-block;padding:2px 9px;border-radius:100px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.4px}

/* Amount columns */
.col-charge{color:#059669;font-weight:600}
.col-payment{color:#2563eb;font-weight:600}
.col-dash{color:#d1d5db}

/* Balance column */
.bal-owed{color:#d97706;font-weight:700}
.bal-clear{color:#059669;font-weight:700}
.bal-nil{color:#059669;font-weight:700}

/* Closing row */
tr.closing td{font-weight:800;font-size:12px;background:#f1f5f9;border-top:2px solid #cbd5e1;padding-top:14px;padding-bottom:14px}

/* ── Footer ── */
.footer{padding:18px 40px;border-top:1px solid #e5e7eb;background:#f9fafb;display:flex;justify-content:space-between;align-items:center}
.footer-left{font-size:10px;color:#9ca3af;line-height:1.6}
.footer-left strong{font-size:11px;color:#6b7280}
.footer-right{font-size:10px;color:#9ca3af;text-align:right;line-height:1.6}
.footer-right .conf{display:inline-block;margin-top:4px;font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#d1d5db}

/* ── Mobile ── */
@media(max-width:640px){
  body{padding:12px 8px}
  .controls{flex-wrap:wrap;gap:8px;margin-bottom:18px}
  .btn{font-size:12px;padding:9px 14px}
  .page{border-radius:10px}
  .hdr{padding:18px 20px 16px;flex-direction:column;gap:10px}
  .hdr-right{text-align:left}
  .doc-title{font-size:15px}
  .info-row{grid-template-columns:1fr}
  .info-block{padding:14px 20px}
  .info-block+.info-block{border-left:none;border-top:1px solid #e5e7eb}
  .summary-strip{grid-template-columns:1fr 1fr}
  .sum-cell{padding:12px 16px}
  .sum-cell .amt{font-size:15px}
  .sum-cell+.sum-cell:nth-child(3){border-left:none;border-top:1px solid #e5e7eb}
  .balance-highlight{margin:12px 16px;padding:12px 16px;flex-direction:column;align-items:flex-start;gap:6px}
  .bh-value{font-size:20px}
  .section-title{padding:10px 20px}
  .table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
  table{min-width:560px}
  th:first-child,td:first-child{padding-left:20px}
  th:last-child,td:last-child{padding-right:20px}
  td,th{padding:8px 10px}
  .footer{padding:14px 20px;flex-direction:column;align-items:flex-start;gap:6px}
  .footer-right{text-align:left}
}

/* ── Print ── */
@media print{
  body{background:#fff;padding:0;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .controls{display:none}
  .page{max-width:none;box-shadow:none;border-radius:0}
  tbody tr{page-break-inside:avoid}
  .balance-highlight{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .accent-stripe{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  @page{size:A4;margin:1cm 1.2cm}
}
</style>
</head>
<body>

{{-- Screen-only controls --}}
<div class="controls">
    <a href="{{ route('transporters.show', $transporter) }}" class="btn btn-light">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back
    </a>
    <a href="{{ route('transporters.export', $transporter) }}" class="btn btn-light">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
        Export CSV
    </a>
    <button type="button" id="printBtn" class="btn btn-dark">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
        <span id="printBtnLabel">Print / Save as PDF</span>
    </button>
</div>

<script>
(function(){
    var btn = document.getElementById('printBtn');
    var lbl = document.getElementById('printBtnLabel');
    var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

    if (isMobile && navigator.share) {
        lbl.textContent = 'Share / Save as PDF';
        btn.addEventListener('click', function(){
            navigator.share({
                title: document.title,
                url: window.location.href
            }).catch(function(){ window.print(); });
        });
    } else {
        btn.addEventListener('click', function(){ window.print(); });
    }
})();
</script>

<div class="page">

    {{-- Header --}}
    <div class="hdr">
        <div class="hdr-left">
            <div class="co-name">{{ $company->name ?? 'TWINS ERP' }}</div>
            <div class="co-tagline">Fuel &amp; Transport Management</div>
        </div>
        <div class="hdr-right">
            <div class="doc-label">Document type</div>
            <div class="doc-title">Transporter Statement</div>
            <div class="doc-date">Generated {{ now()->format('d M Y') }}</div>
        </div>
    </div>

    {{-- Accent stripe --}}
    <div class="accent-stripe"></div>

    {{-- Info row --}}
    <div class="info-row">
        <div class="info-block">
            <div class="ib-label">Transporter</div>
            <div class="ib-name">{{ $transporter->name }}</div>
            <div class="ib-sub">
                {{ $transporter->type === 'intl' ? 'International' : 'Local' }} transporter
                @if($transporter->country) &middot; {{ $transporter->country }}@endif
                @if($transporter->contact_person)<br>{{ $transporter->contact_person }}@if($transporter->phone) &middot; {{ $transporter->phone }}@endif
                @endif
            </div>
        </div>
        <div class="info-block">
            <div class="ib-label">Statement period</div>
            @if($entries->isNotEmpty())
                <div class="ib-name">{{ $entries->first()->entry_date->format('d M Y') }} – {{ $entries->last()->entry_date->format('d M Y') }}</div>
            @else
                <div class="ib-name" style="color:#9ca3af">No transactions yet</div>
            @endif
            <div class="ib-sub">
                Currency: <strong>{{ $currency }}</strong>
                &nbsp;&middot;&nbsp;
                {{ $entries->count() }} {{ $entries->count() === 1 ? 'transaction' : 'transactions' }}
            </div>
        </div>
    </div>

    {{-- Summary strip --}}
    <div class="summary-strip">
        <div class="sum-cell sum-charges">
            <div class="lbl">Freight charges</div>
            <div class="amt">{{ $sym }}{{ number_format($freightTotal, 2) }}</div>
            <div class="sub">Earned from deliveries</div>
        </div>
        <div class="sum-cell sum-payments">
            <div class="lbl">Payments made</div>
            <div class="amt">{{ $sym }}{{ number_format($paymentTotal, 2) }}</div>
            <div class="sub">Settled invoices</div>
        </div>
        <div class="sum-cell sum-deductions">
            <div class="lbl">Deductions</div>
            <div class="amt">{{ $sym }}{{ number_format($advanceTotal + $shortChargeTotal, 2) }}</div>
            <div class="sub">Advances &amp; short charges</div>
        </div>
        <div class="sum-cell {{ $netPayable > 0.005 ? 'sum-balance-owed' : 'sum-balance-clear' }}">
            <div class="lbl">Net balance</div>
            @if(abs($netPayable) < 0.005)
                <div class="amt sum-balance-clear">Settled</div>
                <div class="sub">No outstanding amount</div>
            @elseif($netPayable > 0)
                <div class="amt">{{ $sym }}{{ number_format($netPayable, 2) }}</div>
                <div class="sub">Still owed to transporter</div>
            @else
                <div class="amt sum-balance-clear">{{ $sym }}{{ number_format(abs($netPayable), 2) }} CR</div>
                <div class="sub">Credit on account</div>
            @endif
        </div>
    </div>

    {{-- Balance highlight --}}
    @if($entries->isNotEmpty())
    <div class="balance-highlight {{ $netPayable > 0.005 ? 'bh-owed' : 'bh-clear' }}">
        <div>
            <div class="bh-label">{{ $netPayable > 0.005 ? 'Outstanding balance owed to transporter' : ($netPayable < -0.005 ? 'Overpayment — credit on account' : 'Account fully settled') }}</div>
            <div class="bh-note">As at {{ now()->format('d M Y') }}</div>
        </div>
        <div class="bh-value">
            @if(abs($netPayable) < 0.005)
                {{ $sym }}0.00
            @elseif($netPayable > 0)
                {{ $sym }}{{ number_format($netPayable, 2) }}
            @else
                {{ $sym }}{{ number_format(abs($netPayable), 2) }}&nbsp;CR
            @endif
        </div>
    </div>
    @endif

    {{-- Ledger section --}}
    <div class="section-title">
        <h2>Transaction detail</h2>
        <span class="cnt">{{ $entries->count() }} {{ $entries->count() === 1 ? 'record' : 'records' }}</span>
    </div>

    @if($entries->isEmpty())
        <div style="padding:52px 40px;text-align:center;color:#9ca3af;font-size:13px">
            No transactions have been recorded for this transporter yet.
        </div>
    @else
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th class="r">Charges</th>
                <th class="r">Payments</th>
                <th class="r">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                @php
                    $isCharge  = $entry->amount > 0;     // freight = positive = owed to transporter
                    $isPayment = $entry->amount < 0;     // payment/advance/short = reduces balance
                    $meta = $typeMeta[$entry->type] ?? ['label' => ucfirst(str_replace('_',' ',$entry->type)), 'badge' => '#f3f4f6', 'text' => '#6b7280'];
                    $bal  = $entry->running_balance;
                @endphp
                <tr>
                    <td class="muted" style="white-space:nowrap">{{ $entry->entry_date->format('d M Y') }}</td>
                    <td>
                        <span class="badge" style="background:{{ $meta['badge'] }};color:{{ $meta['text'] }}">{{ $meta['label'] }}</span>
                    </td>
                    <td style="max-width:240px;color:#374151">{{ $entry->description }}</td>
                    <td class="r">
                        @if($isCharge)
                            <span class="col-charge">{{ $sym }}{{ number_format($entry->amount, 2) }}</span>
                        @else
                            <span class="col-dash">—</span>
                        @endif
                    </td>
                    <td class="r">
                        @if($isPayment)
                            <span class="col-payment">{{ $sym }}{{ number_format(abs($entry->amount), 2) }}</span>
                        @else
                            <span class="col-dash">—</span>
                        @endif
                    </td>
                    <td class="r">
                        @if(abs($bal) < 0.005)
                            <span class="bal-nil">—</span>
                        @elseif($bal > 0)
                            <span class="bal-owed">{{ $sym }}{{ number_format($bal, 2) }}</span>
                        @else
                            <span class="bal-clear">{{ $sym }}{{ number_format(abs($bal), 2) }}&nbsp;CR</span>
                        @endif
                    </td>
                </tr>
            @endforeach

            {{-- Closing row --}}
            <tr class="closing">
                <td colspan="3">Closing balance</td>
                <td class="r col-charge">{{ $sym }}{{ number_format($freightTotal, 2) }}</td>
                <td class="r col-payment">{{ $sym }}{{ number_format($advanceTotal + $shortChargeTotal + $paymentTotal, 2) }}</td>
                <td class="r {{ $netPayable > 0.005 ? 'bal-owed' : 'bal-nil' }}">
                    @if(abs($netPayable) < 0.005)
                        Settled
                    @elseif($netPayable > 0)
                        {{ $sym }}{{ number_format($netPayable, 2) }}
                    @else
                        {{ $sym }}{{ number_format(abs($netPayable), 2) }}&nbsp;CR
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-left">
            <strong>TWINS ERP</strong><br>
            Fuel &amp; Transport Management System
        </div>
        <div class="footer-right">
            Generated {{ now()->format('d M Y, H:i') }}<br>
            {{ $transporter->name }} &middot; {{ $currency }}<br>
            <span class="conf">Confidential — for internal use only</span>
        </div>
    </div>

</div>

</body>
</html>
