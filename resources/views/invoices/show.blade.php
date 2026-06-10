@php
    $accent   = $invoice->company->invoice_accent_color ?: '#10b981';
    $accentBg = $accent . '18'; // ~10% opacity hex approximation via rgba in CSS

    $statusMap = [
        'draft'   => ['label' => 'Draft',        'color' => '#94a3b8'],
        'sent'    => ['label' => 'Outstanding',   'color' => '#3b82f6'],
        'overdue' => ['label' => 'Overdue',       'color' => '#ef4444'],
        'paid'    => ['label' => 'Paid',          'color' => '#10b981'],
        'void'    => ['label' => 'Void',          'color' => '#94a3b8'],
    ];
    $st = $statusMap[$invoice->status] ?? $statusMap['sent'];

    $canEdit = in_array($invoice->status, ['draft', 'sent', 'overdue']);
    $canPay  = in_array($invoice->status, ['sent', 'overdue']);
    $canVoid = $invoice->status !== 'void';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        /* ── Variables ────────────────────────────────────────────────────── */
        :root {
            --accent:    {{ $accent }};
            --accent-bg: {{ $accentBg }};
            --status:    {{ $st['color'] }};
        }

        /* ── Reset ─────────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
        }

        /* ── Action bar (screen only) ───────────────────────────────────────── */
        .action-bar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #0f172a;
            border-bottom: 1px solid rgba(255,255,255,.07);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .action-bar .ab-left { display: flex; align-items: center; gap: 8px; flex: 1; }
        .action-bar .ab-label {
            font-size: 13px;
            font-weight: 600;
            color: #f8fafc;
            letter-spacing: .01em;
        }
        .action-bar .ab-sub {
            font-size: 11px;
            color: #64748b;
        }
        .ab-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .15s;
            white-space: nowrap;
        }
        .ab-btn:hover { opacity: .85; }
        .ab-btn-ghost {
            background: rgba(255,255,255,.08);
            color: #cbd5e1;
            border: 1px solid rgba(255,255,255,.1);
        }
        .ab-btn-primary {
            background: var(--accent);
            color: #fff;
        }
        .ab-btn-danger {
            background: rgba(239,68,68,.15);
            color: #fca5a5;
            border: 1px solid rgba(239,68,68,.2);
        }
        .ab-btn-print {
            background: rgba(255,255,255,.06);
            color: #94a3b8;
            border: 1px solid rgba(255,255,255,.08);
        }

        /* ── Session flash ──────────────────────────────────────────────────── */
        .flash-bar {
            background: #10b981;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            padding: 8px 24px;
        }
        .flash-bar.flash-err { background: #ef4444; }

        /* ── Page wrapper ───────────────────────────────────────────────────── */
        .page-wrap {
            display: flex;
            justify-content: center;
            padding: 40px 24px 80px;
        }

        /* ── Document card ──────────────────────────────────────────────────── */
        .inv {
            background: #ffffff;
            width: 100%;
            max-width: 820px;
            border-radius: 20px;
            box-shadow: 0 4px 40px rgba(0,0,0,.12), 0 1px 4px rgba(0,0,0,.06);
            overflow: hidden;
        }

        /* ── Header band ─────────────────────────────────────────────────────── */
        .inv-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 40px 44px 32px;
            border-bottom: 2px solid var(--accent);
            background: linear-gradient(135deg, rgba(var(--accent-rgb, 16,185,129),.03) 0%, transparent 60%);
        }

        .inv-company { display: flex; flex-direction: column; gap: 10px; }
        .inv-logo {
            height: 52px;
            width: auto;
            object-fit: contain;
            border-radius: 10px;
        }
        .inv-logo-placeholder {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: var(--accent);
            color: #fff;
            font-weight: 700;
        }
        .inv-co-name {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -.02em;
        }
        .inv-co-meta {
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
        }

        .inv-meta-right { text-align: right; }
        .inv-big-label {
            font-size: 36px;
            font-weight: 900;
            color: var(--accent);
            letter-spacing: -.04em;
            line-height: 1;
            margin-bottom: 8px;
        }
        .inv-number {
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 12px;
            font-family: 'Courier New', monospace;
        }
        .inv-dates-grid {
            display: grid;
            grid-template-columns: auto auto;
            column-gap: 16px;
            row-gap: 4px;
            font-size: 12px;
            margin-bottom: 12px;
        }
        .inv-dates-grid .dk { color: #94a3b8; text-align: right; }
        .inv-dates-grid .dv { color: #1e293b; font-weight: 600; text-align: right; }
        .inv-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            color: var(--status);
            background: color-mix(in srgb, var(--status) 12%, transparent);
            border: 1px solid color-mix(in srgb, var(--status) 25%, transparent);
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .inv-status-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--status);
        }

        /* ── Parties ─────────────────────────────────────────────────────────── */
        .inv-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            padding: 28px 44px;
            background: #fafbfc;
            border-bottom: 1px solid #e8ecf0;
        }
        .inv-party + .inv-party {
            border-left: 1px solid #e8ecf0;
            padding-left: 28px;
        }
        .inv-section-label {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 8px;
        }
        .inv-party-name {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .inv-party-detail {
            font-size: 12px;
            color: #64748b;
            line-height: 1.7;
        }

        /* ── Items table ─────────────────────────────────────────────────────── */
        .inv-items {
            padding: 28px 44px 0;
        }
        .inv-table {
            width: 100%;
            border-collapse: collapse;
        }
        .inv-table thead tr {
            background: var(--accent);
        }
        .inv-table thead th {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #fff;
            padding: 10px 12px;
            text-align: left;
        }
        .inv-table thead th:last-child,
        .inv-table thead th:nth-child(3),
        .inv-table thead th:nth-child(4) { text-align: right; }
        .inv-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
        }
        .inv-table tbody tr:last-child { border-bottom: none; }
        .inv-table tbody td {
            padding: 14px 12px;
            font-size: 13px;
            color: #334155;
            vertical-align: top;
        }
        .inv-table tbody td.td-right { text-align: right; }
        .inv-table tbody td.td-num {
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .inv-table tbody td.td-idx {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
            width: 32px;
        }
        .inv-item-desc { font-weight: 600; color: #1e293b; }
        .inv-item-sub  { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        /* ── Totals ──────────────────────────────────────────────────────────── */
        .inv-totals-wrap {
            display: flex;
            justify-content: flex-end;
            padding: 20px 44px 28px;
        }
        .inv-totals {
            min-width: 280px;
        }
        .inv-totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 13px;
            color: #64748b;
        }
        .inv-totals-row + .inv-totals-row {
            border-top: 1px solid #f1f5f9;
        }
        .inv-totals-final {
            border-top: 2px solid var(--accent) !important;
            margin-top: 4px;
            padding-top: 10px !important;
        }
        .inv-totals-final span:first-child {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: .02em;
        }
        .inv-totals-final span:last-child {
            font-size: 20px;
            font-weight: 900;
            color: var(--accent);
            letter-spacing: -.02em;
        }
        .inv-paid-row {
            color: #10b981;
            font-weight: 600;
        }
        .inv-balance-row span:last-child {
            color: #ef4444;
            font-weight: 800;
        }

        /* ── Footer band ─────────────────────────────────────────────────────── */
        .inv-footer {
            background: #f8fafc;
            border-top: 1px solid #e8ecf0;
            padding: 24px 44px 32px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 40px;
        }
        .inv-footer-block { display: flex; flex-direction: column; gap: 4px; }
        .inv-footer-label {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 2px;
        }
        .inv-footer-text {
            font-size: 12px;
            color: #475569;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .inv-thankyou {
            grid-column: 1 / -1;
            border-top: 1px solid #e8ecf0;
            padding-top: 16px;
            text-align: center;
            font-size: 13px;
            font-weight: 500;
            color: #94a3b8;
            font-style: italic;
        }

        /* ── Void overlay ───────────────────────────────────────────────────── */
        .void-stamp {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-20deg);
            font-size: 72px;
            font-weight: 900;
            color: rgba(239,68,68,.12);
            letter-spacing: .1em;
            pointer-events: none;
            z-index: 10;
            border: 8px solid rgba(239,68,68,.12);
            padding: 8px 24px;
            border-radius: 8px;
        }
        .inv-relative { position: relative; }

        /* ── Modals ──────────────────────────────────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 200;
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-card {
            background: #fff;
            border-radius: 20px;
            padding: 28px;
            width: 400px;
            max-width: 90vw;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .modal-title {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 16px;
        }
        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .field-input {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 13px;
            color: #1e293b;
            margin-bottom: 14px;
            outline: none;
            font-family: inherit;
        }
        .field-input:focus { border-color: var(--accent); }
        .field-textarea { resize: vertical; min-height: 72px; }
        .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 4px; }
        .btn-sm {
            font-size: 12px;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: opacity .15s;
        }
        .btn-sm:hover { opacity: .85; }
        .btn-cancel-modal { background: #f1f5f9; color: #64748b; }
        .btn-confirm       { background: var(--accent); color: #fff; }
        .btn-danger        { background: #ef4444; color: #fff; }

        /* ── Mobile responsive ────────────────────────────────────────────────── */
        @media (max-width: 640px) {
            .action-bar {
                padding: 10px 12px;
                flex-wrap: wrap;
                gap: 6px;
            }
            .action-bar .ab-left {
                flex: 0 0 100%;
                flex-wrap: wrap;
            }
            .ab-btn {
                font-size: 11px;
                padding: 6px 10px;
            }
            .page-wrap { padding: 12px 0 60px; }
            .inv { border-radius: 12px; margin: 0 10px; }
            .inv-head {
                flex-direction: column;
                gap: 16px;
                padding: 24px 20px 20px;
            }
            .inv-meta-right { text-align: left; }
            .inv-big-label { font-size: 26px; }
            .inv-parties {
                grid-template-columns: 1fr;
                padding: 20px;
            }
            .inv-party + .inv-party {
                border-left: none;
                border-top: 1px solid #e8ecf0;
                padding-left: 0;
                padding-top: 16px;
                margin-top: 16px;
            }
            .inv-items { padding: 20px 16px 0; }
            .inv-table thead { display: none; }
            .inv-table tbody td {
                display: block;
                padding: 4px 8px;
                font-size: 12px;
            }
            .inv-table tbody td.td-idx { display: none; }
            .inv-table tbody tr {
                display: block;
                border: 1px solid #e8ecf0;
                border-radius: 8px;
                margin-bottom: 8px;
                padding: 8px 0;
            }
            .inv-table tbody td.td-right { text-align: left; }
            .inv-totals-wrap { padding: 16px 20px 20px; }
            .inv-totals { min-width: unset; width: 100%; }
            .inv-footer {
                grid-template-columns: 1fr;
                padding: 20px;
                gap: 16px;
            }
        }

        /* ── Print styles ─────────────────────────────────────────────────────── */
        @page { size: A4; margin: 18mm 14mm; }
        @media print {
            body { background: #fff; }
            .action-bar, .flash-bar, .modal-overlay, .email-dropdown { display: none !important; }
            .page-wrap { padding: 0; }
            .inv {
                border-radius: 0;
                box-shadow: none;
                max-width: none;
                margin: 0;
            }
        }

        /* ── Email dropdown ───────────────────────────────────────────────────── */
        .email-wrap { position: relative; }
        .email-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            background: #1e293b;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 12px;
            min-width: 160px;
            box-shadow: 0 8px 32px rgba(0,0,0,.4);
            z-index: 300;
            overflow: hidden;
        }
        .email-wrap:focus-within .email-dropdown,
        .email-wrap.open .email-dropdown { display: block; }
        .email-opt {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
            text-decoration: none;
            transition: background .12s;
        }
        .email-opt:hover { background: rgba(255,255,255,.08); color: #fff; }
        .email-opt + .email-opt { border-top: 1px solid rgba(255,255,255,.06); }
    </style>
</head>
<body>

{{-- ── Action Bar (screen only) ─────────────────────────────────────────── --}}
<div class="action-bar">
    <div class="ab-left">
        <a href="{{ route('invoices.index') }}" class="ab-btn ab-btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
            Invoices
        </a>
        <span class="ab-label">{{ $invoice->invoice_number }}</span>
        <span class="ab-sub">{{ $invoice->client?->name }}</span>
    </div>

    @if($canEdit)
        <button onclick="document.getElementById('modal-notes').classList.add('open')" class="ab-btn ab-btn-ghost">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
            Edit
        </button>
    @endif

    @if($canPay)
        <button onclick="document.getElementById('modal-pay').classList.add('open')" class="ab-btn ab-btn-primary">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            Record Payment
        </button>
    @endif

    @if($canPay && $invoice->type === 'invoice')
        <button onclick="document.getElementById('modal-credit').classList.add('open')" class="ab-btn ab-btn-ghost">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/></svg>
            Credit Note
        </button>
    @endif

    @if($canVoid)
        <button onclick="document.getElementById('modal-void').classList.add('open')" class="ab-btn ab-btn-danger">
            Void
        </button>
    @endif

    <a href="{{ route('invoices.pdf', $invoice) }}" class="ab-btn ab-btn-print" target="_blank">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download PDF
    </a>

    {{-- Email Client — Gmail / Outlook dropdown --}}
    @php
        $eTo   = rawurlencode($invoice->client?->email ?? '');
        $eSub  = rawurlencode('Invoice ' . $invoice->invoice_number . ' — ' . ($invoice->client?->name ?? ''));
        $eBody = rawurlencode(
            'Dear ' . ($invoice->client?->contact_person ?? $invoice->client?->name ?? 'Client') . ',' . "\n\n" .
            'Please find attached invoice ' . $invoice->invoice_number . '.' . "\n\n" .
            'Amount Due : ' . $invoice->currency . ' ' . number_format((float)$invoice->total - (float)$invoice->paid_amount, 2) . "\n" .
            'Invoice Total: ' . $invoice->currency . ' ' . number_format((float)$invoice->total, 2) . "\n" .
            'Due Date    : ' . $invoice->due_date?->format('d M Y') . "\n" .
            'Terms       : ' . ($invoice->payment_terms ?? 'Net 30') . "\n\n" .
            ($invoice->bank_details ? 'Payment Details:' . "\n" . $invoice->bank_details . "\n\n" : '') .
            'Please do not hesitate to contact us should you have any questions.' . "\n\n" .
            'Kind regards,' . "\n" .
            ($invoice->company?->name ?? '')
        );
        $gmailUrl   = 'https://mail.google.com/mail/u/0/?view=cm&fs=1&tf=1&to=' . $eTo . '&su=' . $eSub . '&body=' . $eBody;
        $outlookUrl = 'https://outlook.live.com/mail/0/deeplink/compose?to=' . $eTo . '&subject=' . $eSub . '&body=' . $eBody;
        $mailtoUrl  = 'mailto:' . ($invoice->client?->email ?? '') . '?subject=' . $eSub . '&body=' . $eBody;
    @endphp
    <div class="email-wrap" id="emailWrap">
        <button onclick="document.getElementById('emailWrap').classList.toggle('open')"
                class="ab-btn ab-btn-ghost"
                title="Email this invoice">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Email
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="email-dropdown">
            <a href="{{ $gmailUrl }}" target="_blank" rel="noopener" class="email-opt"
               onclick="document.getElementById('emailWrap').classList.remove('open')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Gmail (web)
            </a>
            <a href="{{ $outlookUrl }}" target="_blank" rel="noopener" class="email-opt"
               onclick="document.getElementById('emailWrap').classList.remove('open')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                Outlook (web)
            </a>
            <a href="{{ $mailtoUrl }}" class="email-opt"
               onclick="document.getElementById('emailWrap').classList.remove('open')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.8 19.8 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
                Mail App (iPhone / default)
            </a>
        </div>
    </div>

    {{-- Print — open in new tab so it works outside the iframe --}}
    <button onclick="window.open('{{ route('invoices.show', $invoice) }}', '_blank').onload = function(){ this.print(); }"
            class="ab-btn ab-btn-print">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print
    </button>
</div>

@if(session('status'))
    <div class="flash-bar">{{ session('status') }}</div>
@endif
@if(session('error'))
    <div class="flash-bar flash-err">{{ session('error') }}</div>
@endif

{{-- ── Invoice Document ──────────────────────────────────────────────────── --}}
<div class="page-wrap">
<div class="inv inv-relative">

    @if($invoice->status === 'void')
        <div class="void-stamp">VOID</div>
    @endif

    {{-- Header --}}
    <div class="inv-head">
        <div class="inv-company">
            @if($invoice->company->logo_path)
                <img src="{{ asset('storage/'.$invoice->company->logo_path) }}"
                     alt="Logo" class="inv-logo">
            @else
                <div class="inv-logo-placeholder">
                    {{ strtoupper(substr($invoice->company->name, 0, 1)) }}
                </div>
            @endif
            <div class="inv-co-name">{{ $invoice->company->name }}</div>
            <div class="inv-co-meta">
                @if($invoice->company->country){{ $invoice->company->country }}@endif
                @if($invoice->company->base_currency)<br>Currency: {{ $invoice->company->base_currency }}@endif
            </div>
        </div>

        <div class="inv-meta-right">
            <div class="inv-big-label">INVOICE</div>
            <div class="inv-number">{{ $invoice->invoice_number }}</div>
            <div class="inv-dates-grid">
                <span class="dk">Issued</span>
                <span class="dv">{{ $invoice->issued_date->format('d M Y') }}</span>
                <span class="dk">Due</span>
                <span class="dv">{{ $invoice->due_date->format('d M Y') }}</span>
                @if($invoice->days_overdue > 0)
                    <span class="dk">Overdue</span>
                    <span class="dv" style="color:#ef4444">{{ $invoice->days_overdue }} days</span>
                @endif
            </div>
            <div>
                <span class="inv-status-badge">
                    <span class="inv-status-dot"></span>
                    {{ strtoupper($st['label']) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Parties --}}
    <div class="inv-parties">
        <div class="inv-party">
            <div class="inv-section-label">Bill To</div>
            <div class="inv-party-name">{{ $invoice->client?->name ?? '—' }}</div>
            <div class="inv-party-detail">
                @if($invoice->client?->contact_person){{ $invoice->client->contact_person }}<br>@endif
                @if($invoice->client?->phone){{ $invoice->client->phone }}<br>@endif
                @if($invoice->client?->email){{ $invoice->client->email }}<br>@endif
                @if($invoice->client?->city || $invoice->client?->country)
                    {{ implode(', ', array_filter([$invoice->client->city, $invoice->client->country])) }}
                @endif
            </div>
        </div>

        <div class="inv-party" style="padding-left:28px">
            <div class="inv-section-label">Reference</div>
            @if($invoice->sale)
                <div class="inv-party-name">{{ $invoice->sale->reference }}</div>
                <div class="inv-party-detail">
                    Sale · {{ $invoice->sale->sale_date?->format('d M Y') ?? '—' }}<br>
                    @if($invoice->payment_terms){{ $invoice->payment_terms }}@endif
                </div>
            @else
                <div class="inv-party-name">{{ $invoice->invoice_number }}</div>
                <div class="inv-party-detail">
                    @if($invoice->payment_terms){{ $invoice->payment_terms }}@endif
                </div>
            @endif

            @if($invoice->status === 'paid' && $invoice->paid_at)
                <div style="margin-top:12px">
                    <div class="inv-section-label" style="color:#10b981">Paid</div>
                    <div class="inv-party-detail" style="color:#10b981;font-weight:600">
                        {{ $invoice->paid_at->format('d M Y') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Items --}}
    <div class="inv-items">
        <table class="inv-table">
            <thead>
                <tr>
                    <th style="width:32px">#</th>
                    <th>Description</th>
                    <th style="width:100px;text-align:right">Qty</th>
                    <th style="width:120px;text-align:right">Unit Price</th>
                    <th style="width:130px;text-align:right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td class="td-idx">{{ $loop->iteration }}</td>
                    <td>
                        <div class="inv-item-desc">{{ $item->description }}</div>
                    </td>
                    <td class="td-right td-num">{{ number_format($item->qty, 0) }}</td>
                    <td class="td-right td-num">
                        {{ $invoice->currency }} {{ number_format($item->unit_price, 4) }}
                    </td>
                    <td class="td-right td-num" style="font-weight:700;color:#1e293b">
                        {{ $invoice->currency }} {{ number_format($item->amount, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Totals --}}
    <div class="inv-totals-wrap">
        <div class="inv-totals">
            <div class="inv-totals-row">
                <span>Subtotal</span>
                <span style="font-family:'Courier New',monospace;font-size:13px">
                    {{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}
                </span>
            </div>
            @if($invoice->discount_amount > 0)
            <div class="inv-totals-row">
                <span>Discount</span>
                <span style="font-family:'Courier New',monospace;font-size:13px;color:#ef4444">
                    − {{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}
                </span>
            </div>
            @endif
            <div class="inv-totals-row">
                <span>Tax ({{ number_format($invoice->tax_rate, 1) }}%)</span>
                <span style="font-family:'Courier New',monospace;font-size:13px">
                    {{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}
                </span>
            </div>
            <div class="inv-totals-row inv-totals-final">
                <span>Total Due</span>
                <span>{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</span>
            </div>
            @if($invoice->paid_amount > 0)
            <div class="inv-totals-row inv-paid-row" style="border-top:1px solid #f1f5f9;margin-top:4px;padding-top:8px">
                <span>Paid</span>
                <span style="font-family:'Courier New',monospace;font-size:13px">
                    − {{ $invoice->currency }} {{ number_format($invoice->paid_amount, 2) }}
                </span>
            </div>
            <div class="inv-totals-row inv-balance-row">
                <span style="font-weight:700;color:#1e293b">Balance Due</span>
                <span style="font-family:'Courier New',monospace;font-size:14px;font-weight:800">
                    {{ $invoice->currency }} {{ number_format($invoice->balance_due, 2) }}
                </span>
            </div>
            @endif
        </div>
    </div>

    {{-- Footer --}}
    <div class="inv-footer">
        @if($invoice->bank_details)
        <div class="inv-footer-block">
            <div class="inv-footer-label">Bank Details</div>
            <div class="inv-footer-text">{{ $invoice->bank_details }}</div>
        </div>
        @endif

        @if($invoice->payment_terms)
        <div class="inv-footer-block">
            <div class="inv-footer-label">Payment Terms</div>
            <div class="inv-footer-text">{{ $invoice->payment_terms }}</div>
        </div>
        @endif

        @if($invoice->notes)
        <div class="inv-footer-block" style="grid-column: 1 / -1">
            <div class="inv-footer-label">Notes</div>
            <div class="inv-footer-text">{{ $invoice->notes }}</div>
        </div>
        @endif

        @if($invoice->footer_text)
        <div class="inv-footer-block" style="grid-column: 1 / -1">
            <div class="inv-footer-label">Terms & Conditions</div>
            <div class="inv-footer-text">{{ $invoice->footer_text }}</div>
        </div>
        @endif

        <div class="inv-thankyou">Thank you for your business — {{ $invoice->company->name }}</div>
    </div>

</div>
</div>

{{-- ── Modals ─────────────────────────────────────────────────────────────── --}}

{{-- Pay modal --}}
<div class="modal-overlay" id="modal-pay">
    <div class="modal-card">
        <div class="modal-title">Record Payment</div>
        <form method="POST" action="{{ route('invoices.mark-paid', $invoice) }}">
            @csrf
            <label class="field-label">Amount Received ({{ $invoice->currency }})</label>
            <input type="number" name="paid_amount" step="0.01" min="0.01"
                   value="{{ number_format($invoice->balance_due, 2, '.', '') }}"
                   class="field-input" required>
            <label class="field-label">Payment Date</label>
            <input type="date" name="paid_at" value="{{ now()->toDateString() }}"
                   class="field-input" required>
            <div class="modal-actions">
                <button type="button" onclick="document.getElementById('modal-pay').classList.remove('open')"
                        class="btn-sm btn-cancel-modal">Cancel</button>
                <button type="submit" class="btn-sm btn-confirm">Record Payment</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Notes modal --}}
<div class="modal-overlay" id="modal-notes">
    <div class="modal-card" style="width:520px">
        <div class="modal-title">Edit Invoice Details</div>
        <form method="POST" action="{{ route('invoices.update-notes', $invoice) }}">
            @csrf @method('PATCH')
            <label class="field-label">Due Date</label>
            <input type="date" name="due_date" value="{{ $invoice->due_date->toDateString() }}"
                   class="field-input">
            <label class="field-label">Payment Terms</label>
            <input type="text" name="payment_terms" value="{{ $invoice->payment_terms }}"
                   placeholder="e.g. Net 30 days" class="field-input">
            <label class="field-label">Bank Details</label>
            <textarea name="bank_details" class="field-input field-textarea"
                      placeholder="Account name, number, IBAN, SWIFT…">{{ $invoice->bank_details }}</textarea>
            <label class="field-label">Notes (visible to client)</label>
            <textarea name="notes" class="field-input field-textarea"
                      placeholder="Any notes to include on the invoice…">{{ $invoice->notes }}</textarea>
            <label class="field-label">Footer / Terms</label>
            <textarea name="footer_text" class="field-input field-textarea"
                      placeholder="Terms & conditions, legal text…">{{ $invoice->footer_text }}</textarea>
            <div class="modal-actions">
                <button type="button" onclick="document.getElementById('modal-notes').classList.remove('open')"
                        class="btn-sm btn-cancel-modal">Cancel</button>
                <button type="submit" class="btn-sm btn-confirm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Void modal --}}
{{-- ── Credit Note Modal ──────────────────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-credit">
    <div class="modal-box">
        <h3 class="modal-title">Issue Credit Note</h3>
        <p class="modal-sub">Creates a credit note reducing the outstanding balance on this invoice.</p>
        <form method="POST" action="{{ route('invoices.credit-note', $invoice) }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Amount to credit ({{ $invoice->currency }})</label>
                <input type="number" name="amount" step="0.01" min="0.01"
                       max="{{ number_format(max(0, (float)$invoice->total - (float)$invoice->paid_amount), 2, '.', '') }}"
                       value="{{ number_format(max(0, (float)$invoice->total - (float)$invoice->paid_amount), 2, '.', '') }}"
                       class="form-input" required>
                <p style="font-size:11px;color:#94a3b8;margin-top:4px">
                    Balance due: {{ $invoice->currency }} {{ number_format(max(0,(float)$invoice->total-(float)$invoice->paid_amount),2) }}
                </p>
            </div>
            <div class="form-group">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" placeholder="e.g. Return, pricing error, early payment discount…"
                       class="form-input" required maxlength="500">
            </div>
            <div class="form-group">
                <label class="form-label">Credit Note Date</label>
                <input type="date" name="issued_date" value="{{ now()->format('Y-m-d') }}"
                       class="form-input" required>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="document.getElementById('modal-credit').classList.remove('open')"
                        class="btn-sm btn-cancel-modal">Cancel</button>
                <button type="submit" class="btn-sm" style="background:rgba(59,130,246,.15);color:#93c5fd;border:1px solid rgba(59,130,246,.3)">
                    Issue Credit Note
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modal-void">
    <div class="modal-card">
        <div class="modal-title">Void Invoice?</div>
        <p style="font-size:13px;color:#64748b;margin-bottom:20px">
            This marks the invoice as void. The underlying sale and ledger entries are not affected.
            This action cannot be undone.
        </p>
        <form method="POST" action="{{ route('invoices.void', $invoice) }}">
            @csrf
            <div class="modal-actions">
                <button type="button" onclick="document.getElementById('modal-void').classList.remove('open')"
                        class="btn-sm btn-cancel-modal">Cancel</button>
                <button type="submit" class="btn-sm btn-danger">Void Invoice</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Credit Notes issued ────────────────────────────────────────────────── --}}
@if($creditNotes->isNotEmpty())
<div class="page-wrap" style="padding-top:0">
    <div style="width:100%;max-width:820px">
        <div style="background:#fff;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,.07);overflow:hidden;margin-bottom:24px">
            <div style="padding:18px 24px;border-bottom:1px solid #e2e8f0">
                <h3 style="font-size:13px;font-weight:700;color:#1e293b;margin:0">Credit Notes</h3>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:11.5px">
                <thead>
                    <tr style="background:#f8fafc">
                        <th style="padding:10px 16px;text-align:left;color:#64748b;font-weight:600;font-size:10px;text-transform:uppercase;letter-spacing:.05em">Number</th>
                        <th style="padding:10px 16px;text-align:left;color:#64748b;font-weight:600;font-size:10px;text-transform:uppercase;letter-spacing:.05em">Date</th>
                        <th style="padding:10px 16px;text-align:right;color:#64748b;font-weight:600;font-size:10px;text-transform:uppercase;letter-spacing:.05em">Amount</th>
                        <th style="padding:10px 16px;text-align:left;color:#64748b;font-weight:600;font-size:10px;text-transform:uppercase;letter-spacing:.05em">Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($creditNotes as $cn)
                    <tr style="border-top:1px solid #f1f5f9">
                        <td style="padding:10px 16px;font-weight:600;color:#3b82f6">{{ $cn->invoice_number }}</td>
                        <td style="padding:10px 16px;color:#64748b">{{ $cn->issued_date->format('d M Y') }}</td>
                        <td style="padding:10px 16px;text-align:right;font-weight:700;color:#ef4444">
                            {{ $cn->currency }} ({{ number_format(abs((float)$cn->total), 2) }})
                        </td>
                        <td style="padding:10px 16px;color:#64748b">{{ $cn->notes }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ── Audit Trail ────────────────────────────────────────────────────────── --}}
@if($auditLogs->isNotEmpty())
<div class="page-wrap" style="padding-top:0">
    <div style="width:100%;max-width:820px">
        <div style="background:#fff;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,.07);overflow:hidden">
            <div style="padding:18px 24px;border-bottom:1px solid #e2e8f0">
                <h3 style="font-size:13px;font-weight:700;color:#1e293b;margin:0">Audit Trail</h3>
            </div>
            <div style="padding:4px 0">
                @foreach($auditLogs as $log)
                <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 20px;border-bottom:1px solid #f1f5f9">
                    <div style="width:6px;height:6px;border-radius:50%;margin-top:5px;flex-shrink:0;background:{{ match($log->severity) { 'critical' => '#ef4444', 'warning' => '#f59e0b', default => '#10b981' } }}"></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:11.5px;color:#1e293b;font-weight:500">{{ $log->description }}</div>
                        <div style="font-size:10.5px;color:#94a3b8;margin-top:2px">
                            {{ $log->user_name ?? 'System' }} · {{ $log->created_at->format('d M Y, H:i') }}
                        </div>
                    </div>
                    <div style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;flex-shrink:0;
                        background:{{ match($log->severity) { 'critical' => 'rgba(239,68,68,.1)', 'warning' => 'rgba(245,158,11,.1)', default => 'rgba(16,185,129,.1)' } }};
                        color:{{ match($log->severity) { 'critical' => '#ef4444', 'warning' => '#f59e0b', default => '#10b981' } }}">
                        {{ $log->event }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<script>
    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });
</script>
</body>
</html>
