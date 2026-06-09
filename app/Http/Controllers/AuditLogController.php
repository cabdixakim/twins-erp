<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $company = auth()->user()->activeCompany;

        $q = AuditLog::where('company_id', $company->id)
            ->orderByDesc('created_at');

        if ($request->filled('event')) {
            $q->where('event', $request->event);
        }
        if ($request->filled('model_type')) {
            $q->where('model_type', 'like', '%' . $request->model_type . '%');
        }
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->user_id);
        }
        if ($request->filled('from')) {
            $q->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('created_at', '<=', $request->to);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(function ($sub) use ($term) {
                $sub->where('description', 'like', $term)
                    ->orWhere('model_label', 'like', $term)
                    ->orWhere('user_name', 'like', $term);
            });
        }

        $logs = $q->paginate(50)->withQueryString();

        $events = AuditLog::where('company_id', $company->id)
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        $users = AuditLog::where('company_id', $company->id)
            ->whereNotNull('user_id')
            ->distinct()
            ->select('user_id', 'user_name')
            ->get()
            ->unique('user_id');

        return view('admin.audit-log', compact('logs', 'events', 'users'));
    }
}
