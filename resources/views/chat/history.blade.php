@extends('layouts.app')

@section('title', 'Historial - NovaRetail')

@section('content')
<div class="mb-6">
    <h2 class="text-3xl font-semibold">Historial Conversacional</h2>
    <p class="text-slate-500">Trazabilidad de preguntas, respuestas, contexto y fuentes.</p>
</div>

<section class="panel space-y-4">
    @forelse($messages as $message)
        <article class="rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold">{{ \Illuminate\Support\Str::limit($message->question, 120) }}</h3>
                <span class="text-xs text-slate-500">{{ $message->created_at->format('Y-m-d H:i') }}</span>
            </div>

            <p class="text-sm text-slate-700 mb-3">{{ $message->answer }}</p>

            <details class="text-sm">
                <summary class="cursor-pointer text-cyan-700">Ver contexto y fuentes</summary>
                <pre class="mt-2 whitespace-pre-wrap bg-slate-50 p-3 rounded text-xs">{{ $message->context_used }}</pre>
                <pre class="mt-2 whitespace-pre-wrap bg-slate-50 p-3 rounded text-xs">{{ json_encode($message->sources, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </details>
        </article>
    @empty
        <p class="text-slate-500">No hay historial disponible.</p>
    @endforelse
</section>
@endsection
