<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ImportTruck;
use App\Models\Product;
use App\Services\DutyPostingService;

class DutiesController extends Controller
{
    public function index(Request $request)
    {
        $cid = (int) auth()->user()->active_company_id;

        $dateFrom   = $request->query('from');
        $dateTo     = $request->query('to');
        $vendorType = $request->query('vendor_type');
        $vendorId   = $request->query('vendor_id');
        $productId  = $request->query('product_id');
        $status     = $request->query('status');

        $query = DB::table('import_trucks as t')
            ->join('import_nominations as n', 't.nomination_id', '=', 'n.id')
            ->join('purchases as p', 'n.purchase_id', '=', 'p.id')
            ->leftJoin('products as pr', 'p.product_id', '=', 'pr.id')
            ->where('p.company_id', $cid)
            ->whereNotNull('t.duty_vendor_type')
            ->select([
                't.id',
                't.truck_reg',
                't.duty_vendor_type',
                't.duty_vendor_id',
                't.duty_rate_per_1000l',
                't.duty_qty',
                't.duty_amount',
                't.duty_currency',
                't.duty_status',
                't.duty_posted_at',
                't.border_date',
                'p.id as purchase_id',
                'p.reference as purchase_ref',
                'p.product_id',
                'pr.name as product_name',
                'n.id as nomination_id',
            ]);

        if ($dateFrom)   $query->whereDate('t.border_date', '>=', $dateFrom);
        if ($dateTo)     $query->whereDate('t.border_date', '<=', $dateTo);
        if ($vendorType) $query->where('t.duty_vendor_type', $vendorType);
        if ($vendorId)   $query->where('t.duty_vendor_id', (int) $vendorId);
        if ($productId)  $query->where('p.product_id', (int) $productId);
        if ($status)     $query->where('t.duty_status', $status);

        $entries = $query->orderByDesc('t.border_date')->orderByDesc('t.id')->paginate(50)->withQueryString();

        // Resolve vendor names
        $entries->getCollection()->transform(function ($row) {
            $row->vendor_name = match ($row->duty_vendor_type) {
                'customs_authority' => DB::table('duty_vendors')->where('id', $row->duty_vendor_id)->value('name') ?? '—',
                'supplier'          => DB::table('suppliers')->where('id', $row->duty_vendor_id)->value('name') ?? '—',
                'depot'             => DB::table('depots')->where('id', $row->duty_vendor_id)->value('name') ?? '—',
                'transporter'       => DB::table('transporters')->where('id', $row->duty_vendor_id)->value('name') ?? '—',
                'self'              => 'Self',
                default             => '—',
            };
            return $row;
        });

        // Summary totals (unfiltered counts for context)
        $totals = DB::table('import_trucks as t')
            ->join('import_nominations as n', 't.nomination_id', '=', 'n.id')
            ->join('purchases as p', 'n.purchase_id', '=', 'p.id')
            ->where('p.company_id', $cid)
            ->whereNotNull('t.duty_vendor_type')
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN t.duty_status = \'posted\' THEN 1 ELSE 0 END) as posted_count,
                SUM(CASE WHEN t.duty_status = \'pending\' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN t.duty_status = \'waived\' THEN 1 ELSE 0 END) as waived_count,
                SUM(CASE WHEN t.duty_status = \'posted\' THEN t.duty_amount ELSE 0 END) as posted_amount,
                SUM(CASE WHEN t.duty_status = \'pending\' THEN t.duty_amount ELSE 0 END) as pending_amount
            ')
            ->first();

        $products = Product::where('company_id', $cid)->where('is_active', true)->orderBy('name')->get();

        return view('duties.index', compact(
            'entries', 'totals', 'products',
            'dateFrom', 'dateTo', 'vendorType', 'vendorId', 'productId', 'status'
        ));
    }

    public function exportCsv(Request $request)
    {
        $cid = (int) auth()->user()->active_company_id;

        $dateFrom   = $request->query('from');
        $dateTo     = $request->query('to');
        $vendorType = $request->query('vendor_type');
        $status     = $request->query('status');

        $query = DB::table('import_trucks as t')
            ->join('import_nominations as n', 't.nomination_id', '=', 'n.id')
            ->join('purchases as p', 'n.purchase_id', '=', 'p.id')
            ->leftJoin('products as pr', 'p.product_id', '=', 'pr.id')
            ->where('p.company_id', $cid)
            ->whereNotNull('t.duty_vendor_type')
            ->select([
                't.truck_reg',
                't.border_date',
                't.duty_vendor_type',
                't.duty_vendor_id',
                't.duty_qty',
                't.duty_rate_per_1000l',
                't.duty_amount',
                't.duty_currency',
                't.duty_status',
                'p.reference as purchase_ref',
                'pr.name as product_name',
            ]);

        if ($dateFrom)   $query->whereDate('t.border_date', '>=', $dateFrom);
        if ($dateTo)     $query->whereDate('t.border_date', '<=', $dateTo);
        if ($vendorType) $query->where('t.duty_vendor_type', $vendorType);
        if ($status)     $query->where('t.duty_status', $status);

        $rows = $query->orderByDesc('t.border_date')->get();

        $filename = 'duties-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Purchase', 'Product', 'Truck', 'Vendor Type', 'Vendor', 'Qty', 'Rate/1000L', 'Amount', 'Currency', 'Status']);
            foreach ($rows as $row) {
                $vendorName = match ($row->duty_vendor_type) {
                    'customs_authority' => DB::table('duty_vendors')->where('id', $row->duty_vendor_id)->value('name') ?? '—',
                    'supplier'          => DB::table('suppliers')->where('id', $row->duty_vendor_id)->value('name') ?? '—',
                    'depot'             => DB::table('depots')->where('id', $row->duty_vendor_id)->value('name') ?? '—',
                    'transporter'       => DB::table('transporters')->where('id', $row->duty_vendor_id)->value('name') ?? '—',
                    'self'              => 'Self',
                    default             => '—',
                };
                fputcsv($out, [
                    $row->border_date,
                    $row->purchase_ref,
                    $row->product_name,
                    $row->truck_reg,
                    ucfirst(str_replace('_', ' ', $row->duty_vendor_type)),
                    $vendorName,
                    $row->duty_qty,
                    $row->duty_rate_per_1000l,
                    $row->duty_amount,
                    $row->duty_currency,
                    $row->duty_status,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
