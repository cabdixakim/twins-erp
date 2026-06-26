<?php

namespace App\Http\Controllers;

use App\Services\AlertService;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->active_company_id;
        $alerts    = AlertService::getForCompany($companyId);

        // Mark all current alerts as seen so the bell badge clears
        session(['alerts_seen_count' => count($alerts)]);

        return view('alerts.index', compact('alerts'));
    }

    public function markSeen(Request $request)
    {
        $companyId = auth()->user()->active_company_id;
        $count     = AlertService::countForCompany($companyId);

        session(['alerts_seen_count' => $count]);

        return response()->json(['ok' => true, 'seen' => $count]);
    }
}
