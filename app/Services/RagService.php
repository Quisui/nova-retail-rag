<?php

namespace App\Services;

use App\DTOs\ChatAnswerData;
use App\Models\Document;
use App\Repositories\DocumentChunkRepository;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RagService
{
    private const FALLBACK_NOT_FOUND = 'No encontré información suficiente en la base de conocimiento para responder con certeza.';
    private const GREETING_ANSWER = 'Hola. Soy el asistente de conocimiento de NovaRetail. Puedes preguntarme sobre devoluciones, garantías, logística, reclamos, atención al cliente o procedimientos internos.';

    public function __construct(
        private readonly GeminiService $geminiService,
        private readonly DocumentProcessorService $documentProcessorService,
        private readonly DocumentChunkRepository $documentChunkRepository,
    ) {
    }

    public function ingestDocument(Document $document): void
    {
        $document->update(['status' => 'processing']);

        try {
            $rawText = $this->documentProcessorService->extractText(
                storage_path('app/'.$document->path),
                $document->mime_type,
            );

            $cleanText = $this->documentProcessorService->cleanText($rawText);
            $normalized = $this->documentProcessorService->normalizeText($cleanText);
            $chunks = $this->splitIntoChunks($normalized);

            if ($chunks === []) {
                throw new RuntimeException('No fue posible generar chunks del documento.');
            }

            $chunksWithEmbeddings = [];
            foreach ($chunks as $chunk) {
                $embedding = $this->geminiService->createEmbedding($chunk->content);

                $chunksWithEmbeddings[] = [
                    'chunk' => $chunk,
                    'embedding' => $embedding,
                ];
            }

            $this->documentChunkRepository->replaceChunksForDocument($document, $chunksWithEmbeddings);

            $document->update([
                'status' => 'processed',
                'original_text' => $normalized,
                'metadata' => [
                    'chunk_count' => count($chunks),
                    'processed_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Error ingesting document', [
                'document_id' => $document->id,
                'error' => $exception->getMessage(),
            ]);

            $document->update([
                'status' => 'failed',
                'metadata' => array_merge($document->metadata ?? [], [
                    'error' => $exception->getMessage(),
                ]),
            ]);

            throw $exception;
        }
    }

    public function splitIntoChunks(string $text): array
    {
        return $this->documentProcessorService->splitIntoChunks($text);
    }

    public function searchRelevantChunks(string $question, int $limit = 5): array
    {
        try {
            $queryEmbedding = $this->geminiService->createEmbedding($question);
            return $this->documentChunkRepository->searchByEmbedding($queryEmbedding, $limit);
        } catch (\Throwable $exception) {
            Log::warning('RAG search fallback due to embedding failure', [
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    public function answerQuestion(string $question): ChatAnswerData
    {
        if ($this->isGreeting($question)) {
            return new ChatAnswerData(
                answer: self::GREETING_ANSWER,
                contextUsed: '',
                sources: [],
                chunks: [],
            );
        }

        $chunks = $this->searchRelevantChunks($question, 5);
        $context = collect($chunks)->pluck('content')->implode("\n\n---\n\n");
        $sources = collect($chunks)
            ->map(static fn (array $chunk): array => [
                'document_id' => $chunk['document_id'],
                'title' => $chunk['document_title'],
                'filename' => $chunk['filename'],
                'chunk_index' => $chunk['chunk_index'],
                'distance' => $chunk['distance'],
            ])
            ->values()
            ->all();

        try {
            $answer = $this->geminiService->generateAnswer($question, $chunks);
        } catch (\Throwable $exception) {
            Log::error('RAG answer fallback due to generation failure', [
                'error' => $exception->getMessage(),
            ]);
            $answer = self::FALLBACK_NOT_FOUND;
        }

        if ($answer === self::FALLBACK_NOT_FOUND) {
            $generalAnswer = $this->geminiService->generateGeneralAnswer($question);
            $answer = $generalAnswer !== '' ? $generalAnswer : self::FALLBACK_NOT_FOUND;
        }

        return new ChatAnswerData(
            answer: $answer,
            contextUsed: $context,
            sources: $sources,
            chunks: $chunks,
        );
    }

    private function isGreeting(string $question): bool
    {
        $normalized = mb_strtolower(trim($question));

        $greetings = [
            'hola',
            'buenas',
            'buenos dias',
            'buenas tardes',
            'buenas noches',
            'hello',
            'hi',
            'hey',
        ];

        foreach ($greetings as $greeting) {
            if (str_starts_with($normalized, $greeting)) {
                return true;
            }
        }

        return false;
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b) || $a === []) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $index => $value) {
            $av = (float) $value;
            $bv = (float) $b[$index];
            $dot += $av * $bv;
            $normA += $av ** 2;
            $normB += $bv ** 2;
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
