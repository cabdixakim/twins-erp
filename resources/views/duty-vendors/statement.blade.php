<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Duty Statement — {{ $dutyVendor->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; font-size: 13px; color: #1e293b; background: #fff; padding: 32px; }
        h1 { font-size: 20px; font-weight: 700; margin-bottom: 2px; }
        .sub { color: #64748b; font-size: 12px; margin-bottom: 24px; }
        .header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .header-right { text-align: right; font-size: 12px; color: #475569; }
        .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px; }
        .summary-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; }
        .summary-card .label { font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: 4px; }
        .summary-card .val { font-size: 16px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { text-align: left; font-size: 11px; font-weight: 600; color: #64748b; border-bottom: 2px solid #e2e8f0; padding: 8px 10px; }
        th:last-child, td:last-child { text-align: right; }
        td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; font-size: 12px; vertical-align: middle; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 600; border: 1px solid; }
        .badge-duty    { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
        .badge-payment { background: #e0f2fe; color: #0369a1; border-color: #7dd3fc; }
        .badge-adjust  { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
        .debit  { color: #d97706; font-weight: 600; }
        .credit { color: #0ea5e9; font-weight: 600; }
        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; text-align: center; }
        .balance-row { background: #f8fafc; font-weight: 700; }
        @media print { body { padding: 16px; } .no-print { display: none; } }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom:20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
    <form method="GET" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <label style="font-size:12px;font-weight:600;color:#475569;">Period:</label>
        <input type="date" name="from" value="{{ $dateFrom }}"
               style="border:1px solid #e2e8f0;border-radius:6px;padding:5px 8px;font-size:12px;">
        <span style="color:#94a3b8;font-size:12px;">to</span>
        <input type="date" name="to" value="{{ $dateTo }}"
               style="border:1px solid #e2e8f0;border-radius:6px;padding:5px 8px;font-size:12px;">
        <button type="submit" style="padding:5px 14px;background:#1e293b;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">Filter</button>
        @if($dateFrom || $dateTo)
            <a href="?" style="font-size:12px;color:#64748b;text-decoration:underline;">Clear</a>
        @endif
    </form>
    <button onclick="window.print()" style="margin-left:auto;padding:5px 14px;background:#0f172a;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">🖨 Print</button>
</div>

<div class="header-row">
    <div>
        <div style="font-size:11px;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.08em;">Customs Duty Statement</div>
        <h1>{{ $dutyVendor->name }}</h1>
        <div class="sub">
            {{ $dutyVendor->country }}{{ $dutyVendor->code ? ' · ' . $dutyVendor->code : '' }}
        </div>
    </div>
    <div class="header-right">
        <div style="font-weight:700;font-size:16px;">{{ $company->name ?? config('app.name') }}</div>
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

<div class="summary">
    <div class="summary-card">
        <div class="label">Total Duties</div>
        <div class="val" style="color:#d97706;">{{ number_format($chargesTotal, 2) }}</div>
    </div>
    <div class="summary-card">
        <div class="label">Payments Made</div>
        <div class="val" style="color:#0ea5e9;">{{ number_format($paymentTotal, 2) }}</div>
    </div>
    <div class="summary-card">
        <div class="label">Net Payable</div>
        <div class="val" style="color:{{ $netPayable > 0 ? '#d97706' : '#16a34a' }};">
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
                $badgeClass = $e->type === 'duty_charge' ? 'badge-duty' : ($e->type === 'payment' ? 'badge-payment' : 'badge-adjust');
                $typeLabel  = $e->type === 'duty_charge' ? 'Duty' : ucfirst(str_replace('_', ' ', $e->type));
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

<div class="no-print" style="margin-top:24px;text-align:center;">
    <button onclick="window.print()" style="padding:8px 24px;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:13px;cursor:pointer;">
        Print / Save as PDF
    </button>
</div>

</body>
</html>
