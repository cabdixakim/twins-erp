<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Period Inventory Movement — {{ \Carbon\Carbon::parse($from)->format('d M Y') }} to {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; font-size: 13px; color: #1e293b; background: #fff; padding: 32px; }
        h1 { font-size: 20px; font-weight: 700; margin-bottom: 2px; }
        .sub { color: #64748b; font-size: 12px; margin-bottom: 24px; }
        .header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .header-right { text-align: right; font-size: 12px; color: #475569; }
        .toolbar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .toolbar label { font-size: 12px; font-weight: 600; color: #475569; }
        .toolbar select, .toolbar input[type="date"] {
            border: 1px solid #e2e8f0; border-radius: 6px; padding: 5px 8px; font-size: 12px; color: #1e293b; background: #fff;
        }
        .toolbar button { padding: 5px 14px; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .btn-primary { background: #1e293b; color: #fff; }
        .btn-print { background: #0f172a; color: #fff; margin-left: auto; }
        .back-link { font-size: 12px; color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .back-link:hover { text-decoration: underline; }
        .formula-note { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; font-size: 11.5px; color: #475569; margin-bottom: 20px; }
        .formula-note strong { color: #1e293b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { text-align: left; font-size: 11px; font-weight: 600; color: #64748b; border-bottom: 2px solid #e2e8f0; padding: 10px; text-transform: uppercase; letter-spacing: .04em; }
        th.num, td.num { text-align: right; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; font-size: 13px; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #fafaf9; }
        .prod-name { font-weight: 600; color: #1e293b; }
        .qty { font-weight: 600; }
        .qty-pos { color: #059669; }
        .qty-neg { color: #a855f7; }
        .qty-loss { color: #dc2626; }
        .muted { color: #94a3b8; }
        .avg-cost { font-size: 10.5px; color: #94a3b8; margin-top: 3px; }
        tfoot tr { background: #f8fafc; font-weight: 700; }
        tfoot td { border-top: 2px solid #e2e8f0; border-bottom: none; padding: 12px 10px; }
        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; text-align: center; }
        .empty { padding: 40px; text-align: center; color: #94a3b8; font-size: 13px; }
        @media print {
            body { padding: 16px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print toolbar">
    <a href="{{ route('reports.inventory-position') }}" class="back-link">&larr; Back to Inventory Position</a>
    <form method="GET" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:16px;">
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
        <button type="submit" class="btn-primary">Apply</button>
    </form>
    <button onclick="window.print()" class="btn-print">🖨 Print</button>
</div>

<div class="header-row">
    <div>
        <div style="font-size:11px;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.08em;">Inventory Report</div>
        <h1>Period Inventory Movement</h1>
        <div class="sub">Opening balance + purchases − sales − losses = closing balance, per product</div>
    </div>
    <div class="header-right">
        <div style="font-weight:700;font-size:16px;">{{ auth()->user()?->activeCompany?->name ?? config('app.name') }}</div>
        <div>Printed {{ now()->format('d M Y H:i') }}</div>
        <div style="margin-top:4px;font-weight:600;color:#d97706;">
            Period: {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
        </div>
        <div>Currency: {{ $currency }}</div>
    </div>
</div>

<div class="formula-note">
    <strong>Opening Balance</strong> (carried from prior periods) + <strong>Purchases</strong> (quantity actually purchased in this period,
    whether or not yet physically received into a depot) − <strong>Sales</strong> (dispatched to customers) − <strong>Losses</strong>
    (recoverable + non-recoverable write-offs) = <strong>Closing Balance</strong>. Average cost is the blended weighted-average cost per litre of stock on hand.
</div>

@if(count($movementRows) > 0)
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
    Generated by {{ config('app.name') }} &nbsp;·&nbsp; {{ now()->format('d M Y H:i') }} &nbsp;·&nbsp; Quantities in litres unless otherwise noted
</div>

</body>
</html>
