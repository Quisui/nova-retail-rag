@extends('layouts.app')

@section('title', 'Dashboard - NovaRetail')

@section('content')
<div class="mb-6">
    <h2 class="text-3xl font-semibold">Dashboard Corporativo</h2>
    <p class="text-slate-500">Vista general del asistente inteligente de conocimiento.</p>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-8">
    <div class="stat-card"><p>Documentos</p><h3>{{ $stats['documents_total'] }}</h3></div>
    <div class="stat-card"><p>Procesados</p><h3>{{ $stats['documents_processed'] }}</h3></div>
    <div class="stat-card"><p>Fallidos</p><h3>{{ $stats['documents_failed'] }}</h3></div>
    <div class="stat-card"><p>Mensajes chat</p><h3>{{ $stats['messages_total'] }}</h3></div>
</div>

<div class="grid gap-6 xl:grid-cols-2">
    <section class="panel">
        <h3 class="panel-title">Documentos recientes</h3>
        <div class="divide-y">
            @forelse($recentDocuments as $doc)
                <a href="{{ route('documents.show', ['document' => $doc->id], absolute: false) }}" class="block py-3 hover:bg-slate-50 px-2 rounded">
                    <p class="font-medium">{{ $doc->title }}</p>
                    <p class="text-sm text-slate-500">Estado: {{ $doc->status }} · {{ $doc->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <p class="text-slate-500">No hay documentos aún.</p>
            @endforelse
        </div>
    </section>

    <section class="panel">
        <h3 class="panel-title">Conversaciones recientes</h3>
        <div class="divide-y">
            @forelse($recentMessages as $msg)
                <div class="py-3 px-2">
                    <p class="font-medium">{{ \Illuminate\Support\Str::limit($msg->question, 100) }}</p>
                    <p class="text-sm text-slate-500">{{ $msg->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-slate-500">No hay conversaciones aún.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
