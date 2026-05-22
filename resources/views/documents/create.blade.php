@extends('layouts.app')

@section('title', 'Subir Documento - NovaRetail')

@section('content')
<div class="mb-6">
    <h2 class="text-3xl font-semibold">Subida de Documentos</h2>
    <p class="text-slate-500">Formatos permitidos: PDF, TXT, MD (máx 10MB).</p>
</div>

<section class="panel max-w-2xl">
    <form method="POST" action="{{ route('documents.store', absolute: false) }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Título</label>
            <input type="text" name="title" value="{{ old('title') }}" class="form-input" required>
        </div>

        <div>
            <label class="form-label">Archivo</label>
            <input type="file" name="document" class="form-input" accept=".pdf,.txt,.md" required>
        </div>

        <button class="btn-primary">Subir y Procesar</button>
    </form>
</section>
@endsection
