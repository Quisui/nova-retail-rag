@extends('layouts.app')

@section('title', 'Detalle Documento - NovaRetail')

@section('content')
<div class="mb-6">
    <h2 class="text-3xl font-semibold">{{ $document->title }}</h2>
    <p class="text-slate-500">{{ $document->filename }} · {{ $document->mime_type }}</p>
</div>

<div class="grid gap-6 xl:grid-cols-2">
    <section class="panel">
        <h3 class="panel-title">Metadatos</h3>
        <div class="text-sm text-slate-600 space-y-2">
            <p><strong>Estado:</strong> {{ $document->status }}</p>
            <p><strong>Chunks:</strong> {{ $document->chunks->count() }}</p>
            <p><strong>Ruta:</strong> {{ $document->path }}</p>
        </div>
    </section>

    <section class="panel">
        <h3 class="panel-title">Preview</h3>
        @if($preview)
            <pre class="whitespace-pre-wrap text-xs bg-slate-50 p-3 rounded max-h-80 overflow-auto">{{ \Illuminate\Support\Str::limit($preview, 5000) }}</pre>
        @else
            <p class="text-slate-500 text-sm">Vista previa no disponible para este formato. Usa el chat para consultar contenido procesado.</p>
        @endif
    </section>
</div>

<section class="panel mt-6">
    <h3 class="panel-title">Chunks generados</h3>
    <div class="space-y-3 max-h-[500px] overflow-auto">
        @forelse($document->chunks as $chunk)
            <article class="rounded-lg border border-slate-200 p-3">
                <p class="text-xs text-slate-500 mb-1">Chunk #{{ $chunk->chunk_index + 1 }}</p>
                <p class="text-sm text-slate-700">{{ $chunk->content }}</p>
            </article>
        @empty
            <p class="text-slate-500">Aún no hay chunks disponibles.</p>
        @endforelse
    </div>
</section>
@endsection
