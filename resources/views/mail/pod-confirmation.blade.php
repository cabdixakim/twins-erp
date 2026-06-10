<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delivery Confirmation</title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #1e293b; background: #f8fafc; margin: 0; padding: 0; }
  .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
  .header { background: #0f172a; padding: 28px 32px; }
  .header-title { font-size: 20px; font-weight: 700; color: #f8fafc; margin: 0 0 4px; }
  .header-sub { font-size: 13px; color: #94a3b8; margin: 0; }
  .body { padding: 32px; }
  .greeting { font-size: 15px; color: #1e293b; margin-bottom: 20px; }
  .highlight-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; }
  .highlight-box p { margin: 0 0 6px; font-size: 13px; color: #166534; }
  .highlight-box p:last-child { margin-bottom: 0; }
  .highlight-box strong { color: #14532d; }
  table.details { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  table.details th { text-align: left; padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
  table.details td { padding: 10px 12px; border: 1px solid #e2e8f0; font-size: 13px; color: #1e293b; }
  table.details td.label { font-weight: 600; color: #475569; white-space: nowrap; width: 36%; }
  .shortfall-box { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; }
  .shortfall-box p { margin: 0 0 4px; font-size: 13px; color: #9a3412; }
  .shortfall-box p:last-child { margin-bottom: 0; }
  .invoice-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; }
  .invoice-box p { margin: 0 0 4px; font-size: 13px; color: #1e40af; }
  .invoice-box p:last-child { margin-bottom: 0; }
  .footer-msg { font-size: 13px; color: #475569; border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 4px; }
  .email-footer { background: #f8fafc; padding: 20px 32px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; text-align: center; }
</style>
</head>
<body>
<div class="wrapper">

  {{-- Header --}}
  <div class="header">
    <p class="header-title">{{ $sale->company?->name ?? 'Twins ERP' }}</p>
    <p class="header-sub">Delivery Confirmation / Confirmation de Livraison</p>
  </div>

  {{-- Body --}}
  <div class="body">

    <p class="greeting">
      Dear {{ $sale->client?->contact_person ?? $sale->client?->name ?? $sale->client_name ?? 'Valued Customer' }},
    </p>

    {{-- Green confirmation box --}}
    <div class="highlight-box">
      <p>✓ <strong>Your delivery has been confirmed.</strong></p>
      <p>✓ <strong>Votre livraison a été confirmée.</strong></p>
    </div>

    {{-- Delivery details --}}
    <table class="details">
      <tr>
        <th colspan="2">Delivery Details / Détails de livraison</th>
      </tr>
      <tr>
        <td class="label">Reference</td>
        <td>{{ $sale->reference }}</td>
      </tr>
      <tr>
        <td class="label">Product / Produit</td>
        <td>{{ $sale->product?->name ?? '—' }}</td>
      </tr>
      <tr>
        <td class="label">Qty Ordered / Qté commandée</td>
        <td>{{ number_format((float) $sale->qty, 3) }} L</td>
      </tr>
      <tr>
        <td class="label">Qty Delivered / Qté livrée</td>
        <td><strong>{{ number_format((float) $sale->qty_delivered, 3) }} L</strong></td>
      </tr>
      <tr>
        <td class="label">POD Date / Date de réception</td>
        <td>{{ $sale->pod_received_at?->format('d M Y') }}</td>
      </tr>
      @if($sale->truck_no)
      <tr>
        <td class="label">Truck / Camion</td>
        <td>{{ $sale->truck_no }}{{ $sale->trailer_no ? ' / ' . $sale->trailer_no : '' }}</td>
      </tr>
      @endif
      @if($sale->driver_name)
      <tr>
        <td class="label">Driver / Chauffeur</td>
        <td>{{ $sale->driver_name }}</td>
      </tr>
      @endif
      @if($sale->waybill_no)
      <tr>
        <td class="label">Waybill / BL</td>
        <td>{{ $sale->waybill_no }}</td>
      </tr>
      @endif
    </table>

    {{-- Shortfall notice if any --}}
    @php
      $shortfall = max(0, (float) $sale->qty - (float) $sale->qty_delivered);
    @endphp
    @if($shortfall > 0.001)
    <div class="shortfall-box">
      <p><strong>Note — Shortfall / Écart de livraison</strong></p>
      <p>Shortfall quantity: {{ number_format($shortfall, 3) }} L</p>
      @if($sale->pod_notes)<p>Remarks: {{ $sale->pod_notes }}</p>@endif
    </div>
    @endif

    {{-- Invoice notice --}}
    <div class="invoice-box">
      <p><strong>Invoice / Facture: {{ $invoice->invoice_number }}</strong></p>
      <p>Amount: {{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</p>
      <p>Due: {{ $invoice->due_date?->format('d M Y') }} ({{ $invoice->payment_terms }})</p>
      <p style="margin-top:8px;font-size:12px;">The invoice is attached to this email as a PDF.</p>
      <p style="font-size:12px;">La facture est jointe à cet e-mail en PDF.</p>
    </div>

    {{-- Bank details if set --}}
    @if($invoice->bank_details)
    <table class="details" style="margin-bottom:24px;">
      <tr>
        <th colspan="2">Payment Details / Détails de paiement</th>
      </tr>
      <tr>
        <td colspan="2" style="white-space:pre-line;font-size:13px;">{{ $invoice->bank_details }}</td>
      </tr>
    </table>
    @endif

    <p class="footer-msg">
      Please do not hesitate to contact us if you have any questions regarding this delivery or the attached invoice.<br>
      <em>N'hésitez pas à nous contacter pour toute question concernant cette livraison ou la facture jointe.</em>
    </p>

  </div>

  {{-- Email footer --}}
  <div class="email-footer">
    {{ $sale->company?->name ?? 'Twins ERP' }}
    @if($sale->company?->email) · {{ $sale->company->email }}@endif
    @if($sale->company?->phone) · {{ $sale->company->phone }}@endif
    <br>This email was generated automatically by Twins ERP.
  </div>

</div>
</body>
</html>
