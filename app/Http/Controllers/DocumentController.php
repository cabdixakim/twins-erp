<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ImportTruck;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->active_company_id;

        $query = Document::where('company_id', $companyId)
            ->with(['uploader'])
            ->orderByDesc('created_at');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $documents = $query->paginate(30)->withQueryString();

        return view('documents.index', compact('documents'));
    }

    public function create()
    {
        $companyId = auth()->user()->active_company_id;

        $trucks = \App\Models\ImportTruck::whereHas('nomination.purchase', fn($q) =>
                $q->where('company_id', $companyId))
            ->with(['nomination.purchase'])
            ->orderByDesc('created_at')
            ->get();

        $purchases = \App\Models\Purchase::where('company_id', $companyId)
            ->whereIn('status', ['confirmed','nominated','received','transferred','dispatched','border_cleared'])
            ->orderByDesc('created_at')
            ->get();

        return view('documents.create', compact('trucks', 'purchases'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file'              => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx',
            'attach_to'         => 'nullable|in:truck,purchase',
            'documentable_id'   => 'nullable|integer',
            'category'          => 'required|in:tr8,t1,customs,invoice,permit,contract,other',
            'name'              => 'nullable|string|max:255',
        ]);

        $companyId = auth()->user()->active_company_id;
        $file      = $request->file('file');

        // Resolve morph target
        $morphType = null;
        $morphId   = null;
        if ($request->filled('attach_to') && $request->filled('documentable_id')) {
            if ($request->attach_to === 'truck') {
                $model = \App\Models\ImportTruck::findOrFail($request->documentable_id);
                $morphType = \App\Models\ImportTruck::class;
                $morphId   = $model->id;
            } elseif ($request->attach_to === 'purchase') {
                $model = \App\Models\Purchase::where('company_id', $companyId)->findOrFail($request->documentable_id);
                $morphType = \App\Models\Purchase::class;
                $morphId   = $model->id;
            }
        }

        $yearMonth = now()->format('Y/m');
        $path = $file->store("documents/{$companyId}/{$yearMonth}", 'local');

        $doc = Document::create([
            'company_id'        => $companyId,
            'documentable_type' => $morphType,
            'documentable_id'   => $morphId,
            'name'              => $request->filled('name') ? $request->name : $file->getClientOriginalName(),
            'original_name'     => $file->getClientOriginalName(),
            'file_path'         => $path,
            'file_size'         => $file->getSize(),
            'mime_type'         => $file->getMimeType(),
            'category'          => $request->category,
            'notes'             => $request->notes,
            'uploaded_by'       => auth()->id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'document' => $doc]);
        }

        return back()->with('success', 'Document uploaded.');
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
        $document->delete();

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Document deleted.');
    }

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
