<?php

namespace App\Http\Controllers;

use App\Services\AlertService;

class AlertController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->active_company_id;
        $alerts    = AlertService::getForCompany($companyId);

        return view('alerts.index', compact('alerts'));
    }
}
