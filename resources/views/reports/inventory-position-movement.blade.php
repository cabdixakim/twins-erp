<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Movement — {{ \Carbon\Carbon::parse($from)->format('d M Y') }} to {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; font-size: 13px; color: #1e293b; background: #e5e7eb; padding: 32px 16px; }

        .controls { display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
        .back-link { font-size: 12px; color: #475569; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-right: 8px; }
        .back-link:hover { text-decoration: underline; }
        .toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .toolbar label { font-size: 12px; font-weight: 600; color: #475569; }
        .toolbar select, .toolbar input[type="date"] {
            border: 1px solid #d1d5db; border-radius: 8px; padding: 6px 10px; font-size: 12px; color: #1e293b; background: #fff;
        }
        .btn { padding: 6px 16px; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; line-height: 1; }
        .btn-primary { background: #1e293b; color: #fff; }
        .btn-print { background: #0f172a; color: #fff; }

        .page { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 8px 40px rgba(0,0,0,.12); overflow: hidden; }

        .hdr { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 28px 40px 24px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .doc-label { font-size: 9px; text-transform: uppercase; letter-spacing: 2.5px; color: #94a3b8; margin-bottom: 6px; }
        .doc-title { font-size: 20px; font-weight: 800; color: #1e293b; letter-spacing: -.3px; }
        .doc-sub { font-size: 11px; color: #64748b; margin-top: 6px; }
        .hdr-right { text-align: right; flex-shrink: 0; }
        .co-name { font-size: 16px; font-weight: 800; color: #1e293b; }
        .hdr-right .period { font-size: 11px; color: #64748b; margin-top: 6px; }

        .accent-stripe { height: 4px; background: linear-gradient(90deg,#0ea5e9 0%,#6366f1 50%,#10b981 100%); }

        .summary-strip { display: grid; grid-template-columns: repeat(4, 1fr); background: #f8fafc; border-bottom: 1px solid #e5e7eb; }
        .sum-cell { padding: 16px 20px; }
        .sum-cell + .sum-cell { border-left: 1px solid #e5e7eb; }
        .sum-cell .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: 1.2px; color: #9ca3af; font-weight: 600; margin-bottom: 6px; }
        .sum-cell .amt { font-size: 17px; font-weight: 800; line-height: 1; }

        .section-title { padding: 14px 40px 10px; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; background: #f9fafb; }
        .section-title h2 { font-size: 12px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: .8px; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 10px; font-weight: 700; color: #6b7280; border-bottom: 2px solid #e5e7eb; padding: 10px 12px; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }
        th:first-child { padding-left: 40px; }
        th:last-child { padding-right: 40px; }
        th.num, td.num { text-align: right; }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 12.5px; vertical-align: top; }
        td:first-child { padding-left: 40px; }
        td:last-child { padding-right: 40px; }
        tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .prod-name { font-weight: 600; color: #1e293b; }
        .qty { font-weight: 600; }
        .qty-pos { color: #059669; }
        .qty-neg { color: #a855f7; }
        .qty-loss { color: #dc2626; }
        .muted { color: #94a3b8; }
        .avg-cost { font-size: 10.5px; color: #94a3b8; margin-top: 3px; }
        tfoot tr { background: #f1f5f9; }
        tfoot td { border-top: 2px solid #cbd5e1; border-bottom: none; padding: 14px 12px; font-weight: 800; }
        tfoot td:first-child { padding-left: 40px; }
        tfoot td:last-child { padding-right: 40px; }

        .footer { padding: 18px 40px; border-top: 1px solid #e5e7eb; background: #f9fafb; font-size: 10.5px; color: #9ca3af; text-align: center; }
        .empty { padding: 48px; text-align: center; color: #94a3b8; font-size: 13px; }

        @media (max-width: 640px) {
            body { padding: 16px 8px; }
            .page { border-radius: 10px; }
            .hdr { padding: 18px 20px 16px; flex-direction: column; gap: 10px; }
            .hdr-right { text-align: left; }
            .summary-strip { grid-template-columns: 1fr 1fr; }
            .sum-cell + .sum-cell:nth-child(3) { border-left: none; border-top: 1px solid #e5e7eb; }
            .section-title { padding: 10px 20px; }
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
    <a href="{{ route('reports.inventory-position') }}" class="back-link">&larr; Back to Inventory Position</a>
    <form method="GET" class="toolbar">
        <label>Period:</label>
        <select name="preset" onchange="this.form.submit()">
            <option value="this_month" @selected($preset === 'this_month')>This Month</option>
            <option value="last_month" @selected($preset === 'last_month')>Last Month</option>
            <option value="this_quarter" @selected($preset === 'this_quarter')>This Quarter</option>
            <option value="this_year" @selected($preset === 'this_year')>This Year</option>
            <option value="custom" @selected($preset === 'custom')>Custom Range</option>
        </select>
        <input type="date" name="from" value="{{ $from }}">
        <span class="muted" style="font-size:12px;">to</span>
        <input type="date" name="to" value="{{ $to }}">
        <button type="submit" class="btn btn-primary">Apply</button>
    </form>
    <button onclick="window.print()" class="btn btn-print">🖨 Print</button>
</div>

<div class="page">
    <div class="hdr">
        <div>
            <div class="doc-label">Statement</div>
            <div class="doc-title">Inventory Movement</div>
            <div class="doc-sub">Opening + purchases − sales − losses = closing, per product</div>
        </div>
        <div class="hdr-right">
            <div class="co-name">{{ auth()->user()?->activeCompany?->name ?? config('app.name') }}</div>
            <div class="period">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>
        </div>
    </div>

    <div class="accent-stripe"></div>

    @if(count($movementRows) > 0)
    <div class="summary-strip">
        <div class="sum-cell">
            <div class="lbl">Opening</div>
            <div class="amt" style="color:#1e293b;">{{ number_format($movementTotals['opening'], 0) }} L</div>
        </div>
        <div class="sum-cell">
            <div class="lbl">Purchases</div>
            <div class="amt" style="color:#059669;">+{{ number_format($movementTotals['purchases'], 0) }} L</div>
        </div>
        <div class="sum-cell">
            <div class="lbl">Sales / Losses</div>
            <div class="amt" style="color:#a855f7;">−{{ number_format($movementTotals['sales'] + $movementTotals['losses'], 0) }} L</div>
        </div>
        <div class="sum-cell">
            <div class="lbl">Closing</div>
            <div class="amt" style="color:{{ $movementTotals['closing'] > 0 ? '#059669' : ($movementTotals['closing'] < 0 ? '#dc2626' : '#1e293b') }};">{{ number_format($movementTotals['closing'], 0) }} L</div>
        </div>
    </div>

    <div class="section-title"><h2>Movement by Product</h2></div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th class="num">Opening Balance</th>
                <th class="num">+ Purchases</th>
                <th class="num">− Sales</th>
                <th class="num">− Losses</th>
                <th class="num">Closing Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movementRows as $row)
            <tr>
                <td class="prod-name">{{ $row['product'] }}</td>
                <td class="num muted">{{ number_format($row['opening'], 0) }} L</td>
                <td class="num">
                    @if($row['purchases'] > 0)
                        <span class="qty qty-pos">+{{ number_format($row['purchases'], 0) }} L</span>
                    @else<span class="muted">—</span>@endif
                </td>
                <td class="num">
                    @if($row['sales'] > 0)
                        <span class="qty qty-neg">−{{ number_format($row['sales'], 0) }} L</span>
                    @else<span class="muted">—</span>@endif
                </td>
                <td class="num">
                    @if($row['losses'] > 0)
                        <span class="qty qty-loss">−{{ number_format($row['losses'], 0) }} L</span>
                    @else<span class="muted">—</span>@endif
                </td>
                <td class="num">
                    <span class="qty" style="color:{{ $row['closing'] > 0 ? '#059669' : ($row['closing'] < 0 ? '#dc2626' : '#1e293b') }}">
                        {{ number_format($row['closing'], 0) }} L
                    </span>
                    @if($row['closing'] > 0.0005)
                        <div class="avg-cost">Avg cost: {{ $currency }} {{ number_format($row['avg_cost'], 2) }}/L</div>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="num muted">{{ number_format($movementTotals['opening'], 0) }} L</td>
                <td class="num" style="color:#059669;">+{{ number_format($movementTotals['purchases'], 0) }} L</td>
                <td class="num" style="color:#a855f7;">−{{ number_format($movementTotals['sales'], 0) }} L</td>
                <td class="num" style="color:#dc2626;">−{{ number_format($movementTotals['losses'], 0) }} L</td>
                <td class="num">
                    {{ number_format($movementTotals['closing'], 0) }} L
                    @if($movementTotals['closing'] > 0.0005)
                        <div class="avg-cost" style="font-weight:600;">Avg cost: {{ $currency }} {{ number_format($movementTotals['avg_cost'], 2) }}/L</div>
                    @endif
                </td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="empty">No inventory activity found for this period.</div>
    @endif

    <div class="footer">
        Generated by {{ config('app.name') }} &nbsp;·&nbsp; {{ now()->format('d M Y H:i') }}
    </div>
</div>

</body>
</html>
