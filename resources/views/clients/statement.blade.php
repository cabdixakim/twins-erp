<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Statement — {{ $client->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; font-size: 13px; color: #1e293b; background: #fff; padding: 32px; }
        h1 { font-size: 20px; font-weight: 700; margin-bottom: 2px; }
        .sub { color: #64748b; font-size: 12px; margin-bottom: 24px; }
        .header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .header-right { text-align: right; font-size: 12px; color: #475569; }
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
        .summary-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; }
        .summary-card .label { font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: 4px; }
        .summary-card .val { font-size: 16px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { text-align: left; font-size: 11px; font-weight: 600; color: #64748b; border-bottom: 2px solid #e2e8f0; padding: 8px 10px; }
        th:last-child, td:last-child { text-align: right; }
        td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; font-size: 12px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 600; border: 1px solid; }
        .badge-invoice    { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
        .badge-payment    { background: #e0f2fe; color: #0369a1; border-color: #7dd3fc; }
        .badge-credit_note{ background: #dcfce7; color: #166534; border-color: #86efac; }
        .badge-adjustment { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
        .debit  { color: #d97706; font-weight: 600; }
        .credit { color: #0ea5e9; font-weight: 600; }
        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; text-align: center; }
        .no-print { margin-bottom: 20px; }
        @media print { .no-print { display: none; } body { padding: 16px; } }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding:8px 20px;border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;font-size:12px;cursor:pointer;font-weight:600;">
        Print / Save PDF
    </button>
    <button onclick="window.close()" style="margin-left:8px;padding:8px 20px;border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;font-size:12px;cursor:pointer;">
        Close
    </button>
</div>

<div class="header-row">
    <div>
        <div style="font-size:11px;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.08em;">Client Statement (AR)</div>
        <h1>{{ $client->name }}</h1>
        <div class="sub">
            {{ $client->country }}{{ $client->country && $client->city ? ', ' : '' }}{{ $client->city }}
            @if($client->contact_person) &nbsp;·&nbsp; {{ $client->contact_person }} @endif
            @if($client->email) &nbsp;·&nbsp; {{ $client->email }} @endif
        </div>
    </div>
    <div class="header-right">
        <div style="font-weight:700;font-size:16px;">{{ $company->name ?? config('app.name') }}</div>
        <div>Printed {{ now()->format('d M Y') }}</div>
        <div>Currency: {{ $currency }}</div>
        @if($client->credit_limit)
            <div>Credit limit: {{ number_format($client->credit_limit, 2) }} {{ $currency }}</div>
        @endif
    </div>
</div>

<div class="summary">
    <div class="summary-card">
        <div class="label">Total Invoiced</div>
        <div class="val" style="color:#d97706;">{{ number_format($invoicedTotal, 2) }} <span style="font-size:11px;font-weight:500;">{{ $currency }}</span></div>
    </div>
    <div class="summary-card">
        <div class="label">Payments Received</div>
        <div class="val" style="color:#0ea5e9;">{{ number_format($paymentTotal, 2) }} <span style="font-size:11px;font-weight:500;">{{ $currency }}</span></div>
    </div>
    <div class="summary-card">
        <div class="label">Credit Notes</div>
        <div class="val" style="color:#16a34a;">{{ number_format($creditTotal, 2) }} <span style="font-size:11px;font-weight:500;">{{ $currency }}</span></div>
    </div>
    <div class="summary-card" style="{{ $netAR > 0 ? 'border-color:#fcd34d;' : '' }}">
        <div class="label">Net Receivable</div>
        <div class="val" style="color:{{ $netAR > 0 ? '#d97706' : ($netAR < 0 ? '#0ea5e9' : '#16a34a') }};">
            @if(abs($netAR) < 0.005) Settled
            @else {{ number_format(abs($netAR), 2) }} {{ $currency }}{{ $netAR < 0 ? ' (cr)' : '' }}
            @endif
        </div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Description</th>
            <th style="text-align:right;">Debit (AR)</th>
            <th style="text-align:right;">Credit (Payment)</th>
            <th style="text-align:right;">Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($entries as $entry)
            @php $isDebit = $entry->amount > 0; @endphp
            <tr>
                <td style="white-space:nowrap;">{{ $entry->entry_date->format('d M Y') }}</td>
                <td><span class="badge badge-{{ $entry->type }}">{{ ucfirst(str_replace('_', ' ', $entry->type)) }}</span></td>
                <td>{{ $entry->description }}</td>
                <td class="{{ $isDebit ? 'debit' : '' }}">{{ $isDebit ? number_format($entry->amount, 2) : '' }}</td>
                <td class="{{ !$isDebit ? 'credit' : '' }}">{{ !$isDebit ? number_format(abs($entry->amount), 2) : '' }}</td>
                <td style="font-weight:600;">{{ number_format($entry->running_balance, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    This statement was generated on {{ now()->format('d M Y \a\t H:i') }} by {{ $company->name ?? config('app.name') }}.
    All amounts in {{ $currency }}. Please contact us if you have any queries.
</div>

</body>
</html>
