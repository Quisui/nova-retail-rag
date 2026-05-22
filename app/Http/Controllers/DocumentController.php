<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Repositories\DocumentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentRepository $documentRepository)
    {
    }

    public function index()
    {
        $documents = $this->documentRepository->latestWithChunkCount();
        return view('documents.index', compact('documents'));
    }

    public function create()
    {
        return view('documents.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document' => ['required', 'file', 'mimes:pdf,txt,md', 'max:10240'],
        ]);

        $file = $validated['document'];
        $filename = Str::uuid()->toString().'_'.$file->getClientOriginalName();
        $storedPath = $file->storeAs('documents', $filename, 'local');

        $document = Document::query()->create([
            'title' => $validated['title'],
            'filename' => $file->getClientOriginalName(),
            'path' => $storedPath,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'status' => 'pending',
            'metadata' => [
                'size' => $file->getSize(),
                'uploaded_at' => now()->toIso8601String(),
            ],
        ]);

        ProcessDocumentJob::dispatch($document->id);

        return redirect()->route('documents.index')->with('success', 'Documento subido y enviado a procesamiento.');
    }

    public function show(int $id)
    {
        $document = $this->documentRepository->byIdWithChunks($id);

        $preview = null;
        if (Storage::disk('local')->exists($document->path) && in_array($document->mime_type, ['text/plain', 'text/markdown', 'text/x-markdown'], true)) {
            $preview = Storage::disk('local')->get($document->path);
        }

        return view('documents.show', compact('document', 'preview'));
    }
}
