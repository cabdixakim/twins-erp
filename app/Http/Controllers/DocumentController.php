<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->active_company_id;

        $query = Document::where('company_id', $companyId)
            ->whereIn('category', Document::$categories)   // vault docs only
            ->with(['uploader'])
            ->orderByRaw("CASE WHEN valid_until IS NULL THEN 1 ELSE 0 END, valid_until ASC")
            ->orderByDesc('created_at');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $today = now()->toDateString();
            match ($request->status) {
                'expired'       => $query->whereNotNull('valid_until')->whereDate('valid_until', '<', $today),
                'expiring_soon' => $query->whereNotNull('valid_until')
                                         ->whereDate('valid_until', '>=', $today)
                                         ->whereDate('valid_until', '<=', now()->addDays(30)->toDateString()),
                'valid'         => $query->whereNotNull('valid_until')->whereDate('valid_until', '>', now()->addDays(30)->toDateString()),
                'no_expiry'     => $query->whereNull('valid_until'),
                default         => null,
            };
        }

        $documents = $query->paginate(30)->withQueryString();

        return view('documents.index', compact('documents'));
    }

    public function create()
    {
        return view('documents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file'        => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx',
            'category'    => 'required|in:' . implode(',', Document::$categories),
            'name'        => 'nullable|string|max:255',
            'valid_from'  => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'notes'       => 'nullable|string|max:2000',
        ]);

        $companyId = auth()->user()->active_company_id;
        $file      = $request->file('file');
        $yearMonth = now()->format('Y/m');
        $path      = $file->store("documents/{$companyId}/{$yearMonth}", 'local');

        $doc = Document::create([
            'company_id'   => $companyId,
            'name'         => $request->filled('name') ? $request->name : $file->getClientOriginalName(),
            'original_name'=> $file->getClientOriginalName(),
            'file_path'    => $path,
            'file_size'    => $file->getSize(),
            'mime_type'    => $file->getMimeType(),
            'category'     => $request->category,
            'valid_from'   => $request->valid_from ?: null,
            'valid_until'  => $request->valid_until ?: null,
            'notes'        => $request->notes,
            'uploaded_by'  => auth()->id(),
        ]);

        \App\Models\AuditLog::record(
            'created',
            "Document '{$doc->name}' ({$doc->category}) uploaded to company vault" .
                ($doc->valid_until ? ", expires {$doc->valid_until->format('d M Y')}" : '') . ".",
            $doc, "Document {$doc->name}",
            severity: 'info',
            module: 'Document',
            after: ['name' => $doc->name, 'category' => $doc->category, 'valid_until' => $doc->valid_until?->toDateString()],
        );

        return redirect()->route('documents.index')->with('success', 'Document uploaded to vault.');
    }

    public function download(Document $document)
    {
        $companyId = auth()->user()->active_company_id;
        abort_if($document->company_id !== $companyId, 403);
        abort_if(! Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    public function destroy(Document $document)
    {
        abort_if($document->company_id !== auth()->user()->active_company_id, 403);

        \App\Models\AuditLog::record(
            'deleted',
            "Document '{$document->name}' ({$document->category}) deleted.",
            $document, "Document {$document->name}",
            severity: 'warning',
            module: 'Document',
            before: ['name' => $document->name, 'category' => $document->category],
        );

        $document->delete();

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Document deleted.');
    }

    /**
     * Internal helper — attaches a file from a request to any model (e.g. ImportTruck).
     * Used by ImportNominationController for border clearance docs.
     * NOT exposed via user-facing routes.
     */
    public static function attachFromRequest(Request $request, object $model, string $fileField, string $category): ?Document
    {
        if (! $request->hasFile($fileField) || ! $request->file($fileField)->isValid()) {
            return null;
        }

        $companyId = auth()->user()->active_company_id;
        $file      = $request->file($fileField);
        $yearMonth = now()->format('Y/m');
        $path      = $file->store("documents/{$companyId}/{$yearMonth}", 'local');

        return Document::create([
            'company_id'        => $companyId,
            'documentable_type' => get_class($model),
            'documentable_id'   => $model->id,
            'name'              => $file->getClientOriginalName(),
            'original_name'     => $file->getClientOriginalName(),
            'file_path'         => $path,
            'file_size'         => $file->getSize(),
            'mime_type'         => $file->getMimeType(),
            'category'          => $category,
            'uploaded_by'       => auth()->id(),
        ]);
    }
}
