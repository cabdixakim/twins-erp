<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        if ($request->filled('severity')) {
            $q->where('severity', $request->severity);
        }
        if ($request->filled('module')) {
            $q->where('module', $request->module);
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

        // CSV export
        if ($request->get('export') === 'csv') {
            return $this->exportCsv(clone $q);
        }

        $logs = $q->paginate(50)->withQueryString();

        $events = AuditLog::where('company_id', $company->id)
            ->distinct()->orderBy('event')->pluck('event');

        $modules = AuditLog::where('company_id', $company->id)
            ->whereNotNull('module')
            ->distinct()->orderBy('module')->pluck('module');

        $users = AuditLog::where('company_id', $company->id)
            ->whereNotNull('user_id')
            ->distinct()
            ->select('user_id', 'user_name')
            ->get()
            ->unique('user_id');

        // Stats for the header bar
        $stats = [
            'total'    => AuditLog::where('company_id', $company->id)->count(),
            'today'    => AuditLog::where('company_id', $company->id)->whereDate('created_at', today())->count(),
            'critical' => AuditLog::where('company_id', $company->id)->where('severity', 'critical')->count(),
            'warning'  => AuditLog::where('company_id', $company->id)->where('severity', 'warning')->count(),
        ];

        return view('admin.audit-log', compact('logs', 'events', 'modules', 'users', 'stats'));
    }

    private function exportCsv($query): StreamedResponse
    {
        $filename = 'audit-log-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date/Time', 'Severity', 'Module', 'Event', 'Description', 'Record', 'User', 'IP Address', 'URL', 'Method']);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $log) {
                    fputcsv($handle, [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->severity,
                        $log->module ?? '',
                        $log->event,
                        $log->description,
                        $log->model_label ?? '',
                        $log->user_name ?? 'System',
                        $log->ip_address ?? '',
                        $log->url ?? '',
                        $log->method ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
