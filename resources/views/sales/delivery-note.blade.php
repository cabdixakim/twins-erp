{{-- Standalone printable Delivery Note / Bon de Livraison --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delivery Note {{ $sale->reference }} / Bon de Livraison</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 11px;
      color: #111;
      background: #fff;
      padding: 20px 28px;
    }
    .no-print { margin-bottom: 16px; display: flex; gap: 8px; }
    .btn {
      padding: 7px 18px;
      border: 1px solid #ccc;
      border-radius: 6px;
      cursor: pointer;
      font-size: 12px;
      background: #f5f5f5;
    }
    .btn-primary { background: #1a56db; color: #fff; border-color: #1a56db; }

    /* Header */
    .doc-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #111;
      padding-bottom: 10px;
      margin-bottom: 14px;
      gap: 16px;
    }
    .company-logo { height: 56px; max-width: 140px; object-fit: contain; margin-bottom: 6px; }
    .company-name { font-size: 14px; font-weight: 700; margin-bottom: 3px; }
    .company-info { font-size: 10px; color: #444; line-height: 1.6; }
    .doc-title-box { text-align: right; min-width: 200px; }
    .doc-title { font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .doc-subtitle { font-size: 10px; color: #555; margin-top: 1px; }
    .doc-ref-table { margin-top: 8px; font-size: 11px; }
    .doc-ref-table td { padding: 1px 0; }
    .doc-ref-table td:first-child { font-weight: 600; padding-right: 8px; white-space: nowrap; }

    /* Info grid */
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .info-table td { padding: 5px 6px; vertical-align: top; border: 1px solid #ccc; font-size: 11px; }
    .info-table td.lbl { font-weight: 700; white-space: nowrap; background: #f8f8f8; width: 18%; }

    /* Seal table */
    .seal-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .seal-table th {
      background: #111;
      color: #fff;
      padding: 6px 8px;
      text-align: left;
      font-size: 11px;
      font-weight: 600;
    }
    .seal-table td { padding: 5px 8px; border: 1px solid #ccc; font-size: 11px; vertical-align: middle; }
    .seal-table tr.total-row td { background: #f0f0f0; font-weight: 700; border-color: #111; }

    /* Footer specs */
    .specs-row { display: flex; gap: 40px; margin-bottom: 20px; font-size: 11px; }
    .spec { display: flex; gap: 6px; align-items: center; }
    .spec-label { font-weight: 700; }

    /* Signatures */
    .sig-row { display: flex; justify-content: space-between; margin-top: 28px; gap: 30px; }
    .sig-box { flex: 1; border-top: 1px solid #111; padding-top: 8px; }
    .sig-title { font-size: 11px; font-weight: 700; margin-bottom: 4px; }
    .sig-sub { font-size: 10px; color: #555; }
    .sig-line { margin-top: 34px; border-top: 1px solid #aaa; padding-top: 4px; font-size: 10px; color: #555; }

    @media print {
      .no-print { display: none !important; }
      body { padding: 14px 18px; }
      @page { margin: 12mm 14mm; }
    }
  </style>
</head>
<body>

  {{-- Screen-only action bar --}}
  <div class="no-print">
    <button class="btn btn-primary" onclick="window.print()">🖨 Print / Imprimer</button>
    <button class="btn" onclick="window.close()">Close</button>
  </div>

  {{-- ===== DOCUMENT HEADER ===== --}}
  <div class="doc-header">
    {{-- Left: company branding --}}
    <div>
      @if($company->logo_path)
        <img src="{{ asset('storage/'.$company->logo_path) }}" class="company-logo" alt="{{ $company->name }}">
        <br>
      @endif
      <div class="company-name">{{ $company->name }}</div>
      <div class="company-info">
        @if($company->address){!! nl2br(e($company->address)) !!}<br>@endif
        @if($company->phone)Tél: {{ $company->phone }}<br>@endif
        @if($company->email){{ $company->email }}<br>@endif
        @if($company->website){{ $company->website }}<br>@endif
        @if($company->rccm)RCCM: {{ $company->rccm }}<br>@endif
        @if($company->id_nat)ID NAT: {{ $company->id_nat }}<br>@endif
        @if($company->nif)NIF: {{ $company->nif }}@endif
      </div>
    </div>

    {{-- Right: document title + reference --}}
    <div class="doc-title-box">
      <div class="doc-title">Delivery Note</div>
      <div class="doc-subtitle">Bon de Livraison</div>
      <table class="doc-ref-table">
        <tr><td>N°:</td><td>{{ $sale->reference }}</td></tr>
        <tr><td>Date:</td><td>{{ $sale->sale_date?->format('d/m/Y') }}</td></tr>
        <tr><td>Depot / Dépôt:</td><td>{{ $sale->depot?->name ?? '—' }}</td></tr>
        <tr><td>Product / Produit:</td><td>{{ $sale->product?->name ?? '—' }}</td></tr>
      </table>
    </div>
  </div>

  {{-- ===== CLIENT / TRANSPORT INFO ===== --}}
  <table class="info-table">
    <tr>
      <td class="lbl">Company / Site:</td>
      <td>{{ $sale->client?->name ?? $sale->client_name ?? '—' }}</td>
      <td class="lbl">Date:</td>
      <td>{{ $sale->sale_date?->format('d/m/Y') }}</td>
    </tr>
    <tr>
      <td class="lbl">Destination:</td>
      <td>{{ $sale->client?->city ?? ($sale->delivery_notes ? \Illuminate\Support\Str::limit($sale->delivery_notes, 60) : '—') }}</td>
      <td class="lbl">Driver / Chauffeur:</td>
      <td>{{ $sale->driver_name ?? '—' }}</td>
    </tr>
    <tr>
      <td class="lbl">Delivery Note N°:</td>
      <td>{{ $sale->reference }}</td>
      <td class="lbl">Truck / Trailer:</td>
      <td>{{ $sale->truck_no ?? '—' }}{{ $sale->trailer_no ? ' / ' . $sale->trailer_no : '' }}</td>
    </tr>
    @if($sale->waybill_no)
    <tr>
      <td class="lbl">Waybill:</td>
      <td colspan="3">{{ $sale->waybill_no }}</td>
    </tr>
    @endif
  </table>

  {{-- ===== SEAL NUMBERS TABLE ===== --}}
  <table class="seal-table">
    <thead>
      <tr>
        <th style="width:38%">Product / Produit</th>
        <th style="width:24%">Qty / Quantité</th>
        <th style="width:38%">Seal No / N° Scellé</th>
      </tr>
    </thead>
    <tbody>
      @if(empty($sealNumbers))
        <tr>
          <td>{{ $sale->product?->name }}</td>
          <td>{{ number_format((float)$sale->qty, 3) }} L</td>
          <td>—</td>
        </tr>
      @else
        @foreach($sealNumbers as $idx => $seal)
          <tr>
            <td>{{ $idx === 0 ? ($sale->product?->name) : '' }}</td>
            <td>{{ $idx === 0 ? number_format((float)$sale->qty, 3) . ' L' : '' }}</td>
            <td>{{ $seal }}</td>
          </tr>
        @endforeach
      @endif
      <tr class="total-row">
        <td>Total</td>
        <td>{{ number_format((float)$sale->qty, 3) }} L</td>
        <td></td>
      </tr>
    </tbody>
  </table>

  {{-- ===== TEMPERATURE / DENSITY ===== --}}
  <div class="specs-row">
    <div class="spec">
      <span class="spec-label">Temperature / Température:</span>
      <span>{{ $sale->temperature !== null ? number_format((float)$sale->temperature, 1) : '20.0' }} °C</span>
    </div>
    <div class="spec">
      <span class="spec-label">Density / Densité:</span>
      <span>{{ $sale->density !== null ? number_format((float)$sale->density, 3) : '—' }} t/m³</span>
    </div>
    <div class="spec">
      <span class="spec-label">Qty ordered / Quantité commandée:</span>
      <span>{{ number_format((float)$sale->qty, 3) }} L</span>
    </div>
  </div>

  {{-- ===== SIGNATURE AREAS ===== --}}
  <div class="sig-row">
    <div class="sig-box">
      <div class="sig-title">Issued by / Émis par</div>
      <div class="sig-sub">{{ $company->name }}</div>
      <div class="sig-line">Date: ________________________</div>
      <div class="sig-line" style="margin-top:8px">Name / Nom: ________________________</div>
      <div class="sig-line" style="margin-top:8px">Signature: ________________________</div>
    </div>
    <div class="sig-box">
      <div class="sig-title">Driver / Chauffeur</div>
      <div class="sig-sub">{{ $sale->driver_name ?? '—' }}</div>
      <div class="sig-line">Date: ________________________</div>
      <div class="sig-line" style="margin-top:8px">Signature: ________________________</div>
    </div>
    <div class="sig-box">
      <div class="sig-title">Received by / Reçu par</div>
      <div class="sig-sub">{{ $sale->client?->name ?? $sale->client_name ?? '—' }}</div>
      <div class="sig-line">Date: ________________________</div>
      <div class="sig-line" style="margin-top:8px">Name / Nom: ________________________</div>
      <div class="sig-line" style="margin-top:8px">Signature + Stamp: _______________</div>
    </div>
  </div>

  {{-- Footer note --}}
  @if($company->invoice_footer_notes)
    <div style="margin-top:20px;border-top:1px solid #ccc;padding-top:8px;font-size:10px;color:#777;text-align:center;">
      {{ $company->invoice_footer_notes }}
    </div>
  @endif

</body>
</html>
