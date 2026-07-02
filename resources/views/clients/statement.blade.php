<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Statement — {{ $client->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; font-size: 13px; color: #1e293b; background: #e5e7eb; padding: 32px 16px; }

        .controls { display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
        .back-link { font-size: 12px; color: #475569; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-right: 8px; }
        .back-link:hover { text-decoration: underline; }
        .btn { padding: 6px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; line-height: 1; }
        .btn-light { background: #f8fafc; color: #1e293b; border: 1px solid #e2e8f0; }
        .btn-print { background: #0f172a; color: #fff; border: none; }

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
        .badge-invoice    { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
        .badge-payment    { background: #e0f2fe; color: #0369a1; border-color: #7dd3fc; }
        .badge-credit_note{ background: #dcfce7; color: #166534; border-color: #86efac; }
        .badge-adjustment { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
        .debit  { color: #d97706; font-weight: 600; }
        .credit { color: #0ea5e9; font-weight: 600; }

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
    <a href="{{ route('clients.show', $client) }}" class="back-link">&larr; Back to {{ $client->name }}</a>
    <button onclick="window.print()" class="btn btn-print">🖨 Print / Save PDF</button>
    <button onclick="window.close()" class="btn btn-light">Close</button>
</div>

<div class="page">
    <div class="hdr">
        <div>
            <div class="doc-label">Statement (AR)</div>
            <div class="doc-title">{{ $client->name }}</div>
            <div class="doc-sub">
                {{ $client->country }}{{ $client->country && $client->city ? ', ' : '' }}{{ $client->city }}
                @if($client->contact_person) &nbsp;·&nbsp; {{ $client->contact_person }} @endif
                @if($client->email) &nbsp;·&nbsp; {{ $client->email }} @endif
            </div>
        </div>
        <div class="hdr-right">
            <div class="company">{{ $company->name ?? config('app.name') }}</div>
            <div>Printed {{ now()->format('d M Y') }}</div>
            <div>Currency: {{ $currency }}</div>
            @if($client->credit_limit)
                <div>Credit limit: {{ number_format($client->credit_limit, 2) }} {{ $currency }}</div>
            @endif
        </div>
    </div>

    <div class="accent-stripe"></div>

    <div class="summary-strip">
        <div class="sum-cell">
            <div class="lbl">Total Invoiced</div>
            <div class="amt" style="color:#d97706;">{{ number_format($invoicedTotal, 2) }} <span style="font-size:11px;font-weight:500;">{{ $currency }}</span></div>
        </div>
        <div class="sum-cell">
            <div class="lbl">Payments Received</div>
            <div class="amt" style="color:#0ea5e9;">{{ number_format($paymentTotal, 2) }} <span style="font-size:11px;font-weight:500;">{{ $currency }}</span></div>
        </div>
        <div class="sum-cell">
            <div class="lbl">Credit Notes</div>
            <div class="amt" style="color:#16a34a;">{{ number_format($creditTotal, 2) }} <span style="font-size:11px;font-weight:500;">{{ $currency }}</span></div>
        </div>
        <div class="sum-cell">
            <div class="lbl">Net Receivable</div>
            <div class="amt" style="color:{{ $netAR > 0 ? '#d97706' : ($netAR < 0 ? '#0ea5e9' : '#16a34a') }};">
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
</div>

</body>
</html>
