<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $invoice->invoice_number }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 11px;
    color: #1e293b;
    background: #fff;
    padding: 40px 44px;
}
@php
    $accent = $invoice->company->invoice_accent_color ?: '#10b981';
@endphp

/* ── Header ─────────────────────────────────────────────── */
.header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 24px;
    border-bottom: 2px solid {{ $accent }};
    margin-bottom: 28px;
}
.company-name {
    font-size: 18px;
    font-weight: 700;
    color: {{ $accent }};
    margin-bottom: 6px;
}
.company-info { color: #64748b; line-height: 1.6; font-size: 10.5px; }
.inv-title { text-align: right; }
.inv-title h1 {
    font-size: 26px;
    font-weight: 800;
    color: {{ $accent }};
    letter-spacing: -0.5px;
    margin-bottom: 4px;
}
.inv-title .inv-number { font-size: 13px; font-weight: 600; color: #475569; }
.inv-title .inv-status {
    display: inline-block;
    margin-top: 6px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    background: {{ $accent }}22;
    color: {{ $accent }};
    border: 1px solid {{ $accent }}55;
}

/* ── Bill-to / Dates row ─────────────────────────────────── */
.meta-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 28px;
    gap: 20px;
}
.meta-block { flex: 1; }
.meta-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
    margin-bottom: 6px;
}
.meta-value { font-size: 11px; color: #1e293b; line-height: 1.55; }
.meta-value strong { font-weight: 700; font-size: 12px; }

/* ── Items table ─────────────────────────────────────────── */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}
thead th {
    background: {{ $accent }};
    color: #fff;
    text-align: left;
    padding: 9px 12px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
thead th:last-child { text-align: right; }
tbody tr:nth-child(even) { background: #f8fafc; }
tbody td {
    padding: 9px 12px;
    vertical-align: top;
    border-bottom: 1px solid #e2e8f0;
    font-size: 11px;
    color: #334155;
}
tbody td:last-child { text-align: right; font-weight: 600; }
tbody td.qty-col, tbody td.unit-col { text-align: right; }

/* ── Totals block ────────────────────────────────────────── */
.totals-wrap {
    display: flex;
    justify-content: flex-end;
    margin-top: 0;
    border-top: 2px solid {{ $accent }};
}
.totals-table { width: 260px; border-collapse: collapse; }
.totals-table td { padding: 7px 12px; font-size: 11px; }
.totals-table td:last-child { text-align: right; font-weight: 600; }
.totals-total td {
    font-size: 13px;
    font-weight: 800;
    color: {{ $accent }};
    padding-top: 10px;
    padding-bottom: 10px;
    border-top: 1px solid {{ $accent }}44;
}
.totals-paid td { color: #10b981; }
.totals-due td { font-size: 13px; font-weight: 800; color: #ef4444; }

/* ── Notes / Footer ──────────────────────────────────────── */
.notes-section {
    margin-top: 28px;
    padding-top: 18px;
    border-top: 1px solid #e2e8f0;
}
.notes-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
    margin-bottom: 6px;
}
.notes-text { font-size: 10.5px; color: #64748b; line-height: 1.6; }
.footer-bar {
    margin-top: 36px;
    padding-top: 14px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
    font-size: 9.5px;
    color: #94a3b8;
}
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div>
        <div class="company-name">{{ $invoice->company->name }}</div>
        <div class="company-info">
            @if($invoice->company->address)
                {!! nl2br(e($invoice->company->address)) !!}<br>
            @endif
            @if($invoice->company->email) {{ $invoice->company->email }}<br> @endif
            @if($invoice->company->phone) {{ $invoice->company->phone }} @endif
        </div>
    </div>
    <div class="inv-title">
        <h1>{{ $invoice->type === 'credit_note' ? 'CREDIT NOTE' : 'INVOICE' }}</h1>
        <div class="inv-number">{{ $invoice->invoice_number }}</div>
        <div class="inv-status">
            @php
                $st = match($invoice->status) {
                    'paid'    => 'Paid',
                    'void'    => 'Void',
                    'overdue' => 'Overdue',
                    default   => 'Outstanding',
                };
            @endphp
            {{ $st }}
        </div>
    </div>
</div>

{{-- Meta row: Bill To + Dates --}}
<div class="meta-row">
    <div class="meta-block">
        <div class="meta-label">Bill To</div>
        <div class="meta-value">
            <strong>{{ $invoice->client?->name ?? '—' }}</strong><br>
            @if($invoice->client?->address) {!! nl2br(e($invoice->client->address)) !!}<br> @endif
            @if($invoice->client?->email) {{ $invoice->client->email }}<br> @endif
            @if($invoice->client?->phone) {{ $invoice->client->phone }} @endif
        </div>
    </div>

    <div class="meta-block" style="text-align:right">
        <div class="meta-label">Invoice Date</div>
        <div class="meta-value" style="margin-bottom:10px">
            {{ $invoice->issued_date->format('d M Y') }}
        </div>
        <div class="meta-label">Due Date</div>
        <div class="meta-value" style="margin-bottom:10px">
            {{ $invoice->due_date->format('d M Y') }}
        </div>
        @if($invoice->payment_terms)
        <div class="meta-label">Payment Terms</div>
        <div class="meta-value">{{ $invoice->payment_terms }}</div>
        @endif
    </div>
</div>

{{-- Line items --}}
<table>
    <thead>
        <tr>
            <th>Description</th>
            <th style="text-align:right">Qty</th>
            <th style="text-align:right">Unit Price</th>
            <th style="text-align:right">Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="qty-col">{{ number_format((float) $item->qty, 2) }}</td>
                <td class="unit-col">{{ number_format((float) $item->unit_price, 4) }}</td>
                <td>{{ $invoice->currency }} {{ number_format((float) $item->amount, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center;color:#94a3b8;padding:20px">No line items</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- Totals --}}
<div class="totals-wrap">
    <table class="totals-table">
        <tr>
            <td style="color:#64748b">Subtotal</td>
            <td>{{ $invoice->currency }} {{ number_format((float) $invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->tax_rate > 0)
        <tr>
            <td style="color:#64748b">Tax ({{ number_format($invoice->tax_rate, 1) }}%)</td>
            <td>{{ $invoice->currency }} {{ number_format((float)($invoice->total - $invoice->subtotal), 2) }}</td>
        </tr>
        @endif
        <tr class="totals-total">
            <td>Total</td>
            <td>{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</td>
        </tr>
        @if($invoice->paid_amount > 0)
        <tr class="totals-paid">
            <td>Paid</td>
            <td>{{ $invoice->currency }} ({{ number_format((float) $invoice->paid_amount, 2) }})</td>
        </tr>
        <tr class="totals-due">
            <td>Balance Due</td>
            <td>{{ $invoice->currency }} {{ number_format(max(0, (float)$invoice->total - (float)$invoice->paid_amount), 2) }}</td>
        </tr>
        @endif
    </table>
</div>

{{-- Bank Details --}}
@if($invoice->bank_details)
<div class="notes-section">
    <div class="notes-label">Bank Details</div>
    <div class="notes-text">{!! nl2br(e($invoice->bank_details)) !!}</div>
</div>
@endif

{{-- Notes --}}
@if($invoice->notes)
<div class="notes-section">
    <div class="notes-label">Notes</div>
    <div class="notes-text">{!! nl2br(e($invoice->notes)) !!}</div>
</div>
@endif

{{-- Footer --}}
<div class="footer-bar">
    @if($invoice->footer_text)
        {{ $invoice->footer_text }}
    @else
        Thank you for your business — {{ $invoice->company->name }}
    @endif
    &nbsp;·&nbsp; Generated {{ now()->format('d M Y') }}
</div>

</body>
</html>
