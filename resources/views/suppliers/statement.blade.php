<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supplier Statement — {{ $supplier->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; font-size: 13px; color: #1e293b; background: #e5e7eb; padding: 32px 16px; }

        .controls { display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
        .back-link { font-size: 12px; color: #475569; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-right: 8px; }
        .back-link:hover { text-decoration: underline; }
        .toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .toolbar label { font-size: 12px; font-weight: 600; color: #475569; }
        .toolbar input[type="date"] {
            border: 1px solid #d1d5db; border-radius: 8px; padding: 6px 10px; font-size: 12px; color: #1e293b; background: #fff;
        }
        .btn { padding: 6px 16px; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; line-height: 1; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-primary { background: #1e293b; color: #fff; }
        .btn-print { background: #0f172a; color: #fff; }

        .page { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 8px 40px rgba(0,0,0,.12); overflow: hidden; }

        .hdr { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 28px 40px 24px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .doc-label { font-size: 9px; text-transform: uppercase; letter-spacing: 2.5px; color: #94a3b8; margin-bottom: 6px; }
        .doc-title { font-size: 20px; font-weight: 800; color: #1e293b; letter-spacing: -.3px; }
        .doc-sub { font-size: 11px; color: #64748b; margin-top: 6px; }
        .hdr-right { text-align: right; flex-shrink: 0; font-size: 12px; color: #64748b; }
        .hdr-right .company { font-weight: 800; font-size: 16px; color: #1e293b; }

        .accent-stripe { height: 4px; background: linear-gradient(90deg,#0ea5e9 0%,#6366f1 50%,#10b981 100%); }

        .summary-strip { display: grid; grid-template-columns: repeat(4, 1fr); background: #f8fafc; border-bottom: 1px solid #e5e7eb; }
        .sum-cell { padding: 16px 20px; }
        .sum-cell + .sum-cell { border-left: 1px solid #e5e7eb; }
        .sum-cell .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: 1.2px; color: #9ca3af; font-weight: 600; margin-bottom: 6px; }
        .sum-cell .amt { font-size: 16px; font-weight: 800; line-height: 1; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 10px; font-weight: 700; color: #6b7280; border-bottom: 2px solid #e5e7eb; padding: 10px 12px; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }
        th:first-child { padding-left: 40px; }
        th:last-child { padding-right: 40px; text-align: right; }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 12px; vertical-align: middle; }
        td:first-child { padding-left: 40px; }
        td:last-child { padding-right: 40px; text-align: right; }
        tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 600; border: 1px solid; }
        .badge-invoice { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
        .badge-payment { background: #e0f2fe; color: #0369a1; border-color: #7dd3fc; }
        .badge-credit  { background: #dcfce7; color: #166534; border-color: #86efac; }
        .badge-adjust  { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
        .debit  { color: #d97706; font-weight: 600; }
        .credit { color: #0ea5e9; font-weight: 600; }
        .balance-row { background: #f1f5f9; font-weight: 800; }
        .balance-row td { border-top: 2px solid #cbd5e1; padding: 14px 12px; }
        .balance-row td:first-child { padding-left: 40px; }
        .balance-row td:last-child { padding-right: 40px; }

        .footer { padding: 18px 40px; border-top: 1px solid #e5e7eb; background: #f9fafb; font-size: 10.5px; color: #9ca3af; text-align: center; }

        @media (max-width: 640px) {
            body { padding: 16px 8px; }
            .page { border-radius: 10px; }
            .hdr { padding: 18px 20px 16px; flex-direction: column; gap: 10px; }
            .hdr-right { text-align: left; }
            .summary-strip { grid-template-columns: 1fr 1fr; }
            .sum-cell + .sum-cell:nth-child(3) { border-left: none; border-top: 1px solid #e5e7eb; }
            th:first-child, td:first-child { padding-left: 20px; }
            th:last-child, td:last-child { padding-right: 20px; }
            .balance-row td:first-child, .balance-row td:last-child { padding-left: 20px; padding-right: 20px; }
            .footer { padding: 14px 20px; }
        }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .page { max-width: none; box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>

<div class="no-print controls">
    <a href="{{ route('suppliers.show', $supplier) }}" class="back-link">&larr; Back to {{ $supplier->name }}</a>
    <form method="GET" class="toolbar">
        <label>Period:</label>
        <input type="date" name="from" value="{{ $dateFrom }}">
        <span class="muted" style="font-size:12px;color:#94a3b8;">to</span>
        <input type="date" name="to" value="{{ $dateTo }}">
        <button type="submit" class="btn btn-primary">Filter</button>
        @if($dateFrom || $dateTo)
            <a href="?" style="font-size:12px;color:#64748b;text-decoration:underline;">Clear</a>
        @endif
    </form>
    <button onclick="window.print()" class="btn btn-print">🖨 Print</button>
</div>

<div class="page">
    <div class="hdr">
        <div>
            <div class="doc-label">Statement</div>
            <div class="doc-title">{{ $supplier->name }}</div>
            <div class="doc-sub">
                {{ $supplier->country }}{{ $supplier->country && $supplier->city ? ', ' : '' }}{{ $supplier->city }}
                @if($supplier->contact_person) &nbsp;·&nbsp; {{ $supplier->contact_person }} @endif
            </div>
        </div>
        <div class="hdr-right">
            <div class="company">{{ $company->name ?? config('app.name') }}</div>
            <div>Printed {{ now()->format('d M Y') }}</div>
            @if($dateFrom || $dateTo)
                <div style="margin-top:4px;font-weight:600;color:#d97706;">
                    Period: {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : 'start' }}
                    — {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : 'today' }}
                </div>
            @endif
            <div>Currency: {{ $currency }}</div>
        </div>
    </div>

    <div class="accent-stripe"></div>

    <div class="summary-strip">
        <div class="sum-cell">
            <div class="lbl">Total Invoiced</div>
            <div class="amt" style="color:#1e293b;">${{ number_format($invoicedTotal, 2) }}</div>
        </div>
        <div class="sum-cell">
            <div class="lbl">Payments Made</div>
            <div class="amt" style="color:#0ea5e9;">${{ number_format($paymentTotal, 2) }}</div>
        </div>
        <div class="sum-cell">
            <div class="lbl">Credit Notes</div>
            <div class="amt" style="color:#16a34a;">${{ number_format($creditTotal, 2) }}</div>
        </div>
        <div class="sum-cell">
            <div class="lbl">Net Payable</div>
            <div class="amt" style="color:{{ $netPayable > 0 ? '#d97706' : '#16a34a' }};">
                {{ $netPayable > 0 ? number_format($netPayable, 2) : ('Overpaid ' . number_format(abs($netPayable), 2)) }}
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th style="text-align:right">Debit</th>
                <th style="text-align:right">Credit</th>
                <th style="text-align:right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @if($dateFrom && $openingBalance != 0)
            <tr style="background:#fafaf7;">
                <td style="white-space:nowrap;color:#64748b;font-style:italic;">{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</td>
                <td><span class="badge badge-adjust">Opening</span></td>
                <td style="color:#64748b;font-style:italic;">Opening balance brought forward</td>
                <td class="debit" style="text-align:right">{{ $openingBalance > 0 ? number_format(abs($openingBalance), 2) : '' }}</td>
                <td class="credit" style="text-align:right">{{ $openingBalance < 0 ? number_format(abs($openingBalance), 2) : '' }}</td>
                <td style="text-align:right;font-weight:600;color:{{ $openingBalance >= 0 ? '#d97706' : '#16a34a' }}">
                    {{ number_format(abs($openingBalance), 2) }}{{ $openingBalance < 0 ? ' CR' : '' }}
                </td>
            </tr>
            @endif
            @foreach($entries as $e)
                @php
                    $isDebit = (float) $e->amount > 0;
                    $badgeClass = match($e->type) {
                        'purchase_invoice' => 'badge-invoice',
                        'payment'          => 'badge-payment',
                        'credit_note'      => 'badge-credit',
                        default            => 'badge-adjust',
                    };
                    $typeLabel = match($e->type) {
                        'purchase_invoice' => 'Invoice',
                        'payment'          => 'Payment',
                        'credit_note'      => 'Credit',
                        'adjustment'       => 'Adjustment',
                        default            => $e->type,
                    };
                @endphp
                <tr>
                    <td style="white-space:nowrap">{{ $e->entry_date->format('d M Y') }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $typeLabel }}</span></td>
                    <td>{{ $e->description }}</td>
                    <td class="debit" style="text-align:right">{{ $isDebit ? number_format(abs((float)$e->amount), 2) : '' }}</td>
                    <td class="credit" style="text-align:right">{{ !$isDebit ? number_format(abs((float)$e->amount), 2) : '' }}</td>
                    <td style="text-align:right;font-weight:600;color:{{ $e->running_balance >= 0 ? '#d97706' : '#16a34a' }}">
                        {{ number_format(abs((float)$e->running_balance), 2) }}
                        {{ $e->running_balance < 0 ? 'CR' : '' }}
                    </td>
                </tr>
            @endforeach
            <tr class="balance-row">
                <td colspan="5" style="text-align:right;font-size:12px;color:#64748b;">Closing balance owed</td>
                <td style="text-align:right;color:{{ $netPayable > 0 ? '#d97706' : '#16a34a' }}">
                    {{ number_format(abs($netPayable), 2) }} {{ $netPayable < 0 ? 'CR' : '' }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Generated by {{ config('app.name') }} &nbsp;·&nbsp; {{ now()->format('d M Y H:i') }} &nbsp;·&nbsp; All amounts in {{ $currency }}
    </div>
</div>

<div class="no-print" style="margin-top:24px;text-align:center;">
    <button onclick="window.print()" class="btn btn-print" style="padding:8px 24px;font-size:13px;">
        Print / Save as PDF
    </button>
</div>

</body>
</html>
