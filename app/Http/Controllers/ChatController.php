<?php

namespace App\Http\Controllers;

use App\Repositories\ChatMessageRepository;
use App\Services\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function __construct(
        private readonly RagService $ragService,
        private readonly ChatMessageRepository $chatMessageRepository,
    ) {
    }

    public function index()
    {
        $messages = $this->chatMessageRepository->latest(50);
        return view('chat.index', compact('messages'));
    }

    public function ask(Request $request)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:2', 'max:1500'],
        ]);

        try {
            $answerData = $this->ragService->answerQuestion($validated['question']);

            $message = $this->chatMessageRepository->create([
                'question' => $validated['question'],
                'answer' => $answerData->answer,
                'context_used' => $answerData->contextUsed,
                'sources' => $answerData->sources,
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'ok' => true,
                    'message_id' => $message->id,
                    'result' => $answerData->toArray(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('Chat ask failed', ['error' => $exception->getMessage()]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'No se pudo procesar la consulta con IA en este momento.',
                ], 500);
            }

            return redirect()->route('chat.index')->with('success', 'No se pudo procesar la consulta con IA en este momento. Intenta nuevamente.');
        }

        return redirect()->route('chat.index')->with('chat_result', $answerData->toArray());
    }

    public function history()
    {
        $messages = $this->chatMessageRepository->latest(200);
        return view('chat.history', compact('messages'));
    }
}
