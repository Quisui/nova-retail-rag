@extends('layouts.app')

@section('title', 'Chat IA - NovaRetail')

@section('content')
<div class="mb-6">
    <h2 class="text-3xl font-semibold">Asistente IA NovaRetail</h2>
    <p class="text-slate-500">Consulta políticas, procesos y conocimiento interno con RAG o conversa en modo asistente.</p>
</div>

<section class="panel" id="chatApp" data-ask-url="{{ route('chat.ask', absolute: false) }}" data-csrf="{{ csrf_token() }}">
    <h3 class="panel-title">Knowledge Chat</h3>

    <div id="chatMessages" class="rounded-xl border border-slate-200 bg-slate-50 p-4 h-[430px] overflow-y-auto overflow-x-hidden space-y-4 mb-5">
        <div class="flex justify-start w-full">
            <div class="max-w-[85%] min-w-0 rounded-xl border border-slate-200 bg-white p-3 text-sm leading-7 break-words [overflow-wrap:anywhere]">
                Hola. Soy el asistente de conocimiento de NovaRetail. Puedes preguntarme sobre devoluciones, garantías, logística, reclamos, atención al cliente o procedimientos internos.
            </div>
        </div>

        @foreach($messages->reverse() as $message)
            <div class="flex justify-end w-full">
                <div class="max-w-[85%] min-w-0 rounded-xl bg-cyan-700 text-white p-3 text-sm leading-7 whitespace-pre-wrap break-words [overflow-wrap:anywhere]">{{ $message->question }}</div>
            </div>
            <div class="flex justify-start w-full">
                <div class="max-w-[85%] min-w-0 rounded-xl border border-slate-200 bg-white p-3 text-sm leading-7 break-words [overflow-wrap:anywhere]">
                    <p class="whitespace-pre-wrap break-words [overflow-wrap:anywhere]">{{ $message->answer }}</p>
                    @if(!empty($message->sources))
                        <details class="mt-2 text-xs text-slate-600">
                            <summary class="cursor-pointer">Ver fuentes</summary>
                            <pre class="mt-2 whitespace-pre-wrap break-words overflow-auto max-h-40 rounded border border-slate-200 bg-slate-50 p-2">{{ json_encode($message->sources, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </details>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mb-4">
        <p class="text-sm font-semibold mb-2">Suggested demo questions</p>
        <div class="flex flex-wrap gap-2" id="suggestedQuestions">
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white hover:bg-slate-100">What is the process to return a defective laptop?</button>
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white hover:bg-slate-100">When should a customer service case be escalated?</button>
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white hover:bg-slate-100">What does the electronics warranty cover?</button>
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white hover:bg-slate-100">¿Cuál es la política de devoluciones de NovaRetail?</button>
        </div>
    </div>

    <form id="liveChatForm" class="space-y-3">
        <textarea id="chatInput" name="question" rows="3" minlength="2" class="form-input" placeholder="Pregunta lo que necesites sobre NovaRetail..."></textarea>
        <div class="flex justify-end">
            <button id="sendButton" type="submit" class="btn-primary">Enviar</button>
        </div>
    </form>
</section>

<script>
(() => {
    const app = document.getElementById('chatApp');
    if (!app) return;

    const askUrl = app.dataset.askUrl;
    const csrf = app.dataset.csrf;
    const form = document.getElementById('liveChatForm');
    const input = document.getElementById('chatInput');
    const sendButton = document.getElementById('sendButton');
    const messages = document.getElementById('chatMessages');
    const suggested = document.getElementById('suggestedQuestions');

    const scrollDown = () => {
        messages.scrollTop = messages.scrollHeight;
    };

        const appendBubble = (role, text, payload = null) => {
            const row = document.createElement('div');
            row.className = role === 'user' ? 'flex justify-end w-full' : 'flex justify-start w-full';

            const bubble = document.createElement('div');
            bubble.className = role === 'user'
                ? 'max-w-[85%] min-w-0 rounded-xl bg-cyan-700 text-white p-3 text-sm leading-7 whitespace-pre-wrap break-words [overflow-wrap:anywhere]'
                : 'max-w-[85%] min-w-0 rounded-xl border border-slate-200 bg-white p-3 text-sm leading-7 break-words [overflow-wrap:anywhere]';

            const textNode = document.createElement('p');
            textNode.className = 'whitespace-pre-wrap break-words [overflow-wrap:anywhere]';
            textNode.textContent = text;
        bubble.appendChild(textNode);

        if (payload && Array.isArray(payload.sources) && payload.sources.length > 0 && role === 'assistant') {
            const details = document.createElement('details');
            details.className = 'mt-2 text-xs text-slate-600';

            const summary = document.createElement('summary');
            summary.className = 'cursor-pointer';
            summary.textContent = 'Ver fuentes';

            const pre = document.createElement('pre');
            pre.className = 'mt-2 whitespace-pre-wrap break-words overflow-auto max-h-40 rounded border border-slate-200 bg-slate-50 p-2';
            pre.textContent = JSON.stringify(payload.sources, null, 2);

            details.appendChild(summary);
            details.appendChild(pre);
            bubble.appendChild(details);
        }

        row.appendChild(bubble);
        messages.appendChild(row);
        scrollDown();

        return row;
    };

    suggested.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button) return;
        input.value = button.textContent.trim();
        input.focus();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const question = input.value.trim();
        if (question.length < 2) return;

        appendBubble('user', question);
        input.value = '';
        sendButton.disabled = true;

        const typing = appendBubble('assistant', 'Pensando...');

        try {
            const response = await fetch(askUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ question }),
            });

            typing.remove();

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.ok) {
                const errorText = data.error || data.message || 'No se pudo procesar la consulta con IA en este momento.';
                appendBubble('assistant', errorText);
                return;
            }

            const result = data.result || {};
            appendBubble('assistant', result.answer || 'Sin respuesta disponible.', result);
        } catch (_error) {
            typing.remove();
            appendBubble('assistant', 'Error de red consultando el asistente. Intenta nuevamente.');
        } finally {
            sendButton.disabled = false;
            input.focus();
        }
    });

    scrollDown();
})();
</script>
@endsection
