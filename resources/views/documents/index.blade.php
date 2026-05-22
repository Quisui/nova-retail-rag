@extends('layouts.app')

@section('title', 'Documentos - NovaRetail')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-3xl font-semibold">Base Documental</h2>
        <p class="text-slate-500">Gestiona y supervisa ingestión de conocimiento interno.</p>
    </div>
    <a href="{{ route('documents.create', absolute: false) }}" class="btn-primary">Subir documento</a>
</div>

<section class="panel overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead>
        <tr class="text-left text-slate-500 border-b">
            <th class="py-3">Título</th>
            <th class="py-3">Tipo</th>
            <th class="py-3">Estado</th>
            <th class="py-3">Chunks</th>
            <th class="py-3">Fecha</th>
            <th class="py-3"></th>
        </tr>
        </thead>
        <tbody>
        @forelse($documents as $doc)
            <tr class="border-b">
                <td class="py-3 font-medium">{{ $doc->title }}</td>
                <td class="py-3">{{ $doc->mime_type }}</td>
                <td class="py-3"><span class="status-pill status-{{ $doc->status }}">{{ $doc->status }}</span></td>
                <td class="py-3">{{ $doc->chunks_count }}</td>
                <td class="py-3">{{ $doc->created_at->format('Y-m-d H:i') }}</td>
                <td class="py-3"><a href="{{ route('documents.show', ['id' => $doc->id], absolute: false) }}" class="text-cyan-700 font-medium">Ver</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="py-6 text-center text-slate-500">No hay documentos cargados.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
