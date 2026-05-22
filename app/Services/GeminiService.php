<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class GeminiService
{
    private const FALLBACK_NOT_FOUND = 'No encontré información suficiente en la base de conocimiento para responder con certeza.';

    public function createEmbedding(string $text): array
    {
        $apiKey = (string) config('services.gemini.api_key');
        $primaryModel = (string) config('services.gemini.embedding_model', 'text-embedding-004');
        $targetDimensions = (int) config('services.gemini.embedding_dimensions', 768);

        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY no está configurada.');
        }

        $payload = [
            'content' => [
                'parts' => [
                    ['text' => $text],
                ],
            ],
            'taskType' => 'RETRIEVAL_DOCUMENT',
        ];

        $modelsToTry = array_values(array_unique([
            $primaryModel,
            'gemini-embedding-001',
            'embedding-001',
        ]));

        foreach ($modelsToTry as $model) {
            try {
                $response = Http::timeout(30)
                    ->retry(2, 500)
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent", $payload)
                    ->throw();

                $values = data_get($response->json(), 'embedding.values', []);

                if (is_array($values) && count($values) > 0) {
                    $embedding = array_map(static fn ($value): float => (float) $value, $values);
                    return $this->normalizeEmbeddingDimensions($embedding, $targetDimensions);
                }

                Log::warning('Gemini embedding returned empty vector', ['model' => $model]);
            } catch (ConnectionException $exception) {
                Log::error('Gemini embedding timeout', [
                    'model' => $model,
                    'message' => $exception->getMessage(),
                ]);
            } catch (\Throwable $exception) {
                $isModelNotFound = Str::contains($exception->getMessage(), ['not found', '404']);
                Log::error('Gemini embedding error', [
                    'model' => $model,
                    'message' => $exception->getMessage(),
                    'model_not_found' => $isModelNotFound,
                ]);
            }
        }

        throw new RuntimeException('Error al generar embedding en Gemini.');
    }

    private function normalizeEmbeddingDimensions(array $embedding, int $targetDimensions): array
    {
        if ($targetDimensions <= 0) {
            return $embedding;
        }

        $count = count($embedding);

        if ($count === $targetDimensions) {
            return $embedding;
        }

        if ($count > $targetDimensions) {
            return array_slice($embedding, 0, $targetDimensions);
        }

        return array_pad($embedding, $targetDimensions, 0.0);
    }

    public function generateAnswer(string $question, array $contextChunks): string
    {
        $apiKey = (string) config('services.gemini.api_key');
        $primaryModel = (string) config('services.gemini.chat_model', 'gemini-1.5-flash');

        if (empty($contextChunks)) {
            return self::FALLBACK_NOT_FOUND;
        }

        if ($apiKey === '') {
            return self::FALLBACK_NOT_FOUND;
        }

        $contextText = collect($contextChunks)
            ->map(static function (array $chunk): string {
                $source = sprintf('%s (chunk %d)', $chunk['document_title'] ?? 'Documento', ($chunk['chunk_index'] ?? 0) + 1);
                return "Fuente: {$source}\nContenido: {$chunk['content']}";
            })
            ->implode("\n\n---\n\n");

        $systemPrompt = "Eres un asistente corporativo de NovaRetail.\nDebes responder únicamente usando el contexto proporcionado.\nNo inventes información.\nSi no existe suficiente contexto responde:\n'No encontré información suficiente en la base de conocimiento para responder con certeza.'";

        $userPrompt = "Contexto recuperado:\n{$contextText}\n\nPregunta: {$question}\n\nResponde en español, de forma clara y accionable.";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt."\n\n".$userPrompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'topP' => 0.9,
                'maxOutputTokens' => 700,
            ],
        ];

        $modelsToTry = array_values(array_unique([
            $primaryModel,
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-flash-latest',
        ]));

        foreach ($modelsToTry as $model) {
            try {
                $response = Http::timeout(40)
                    ->retry(2, 500)
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", $payload)
                    ->throw();

                $answer = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));

                if ($answer !== '') {
                    return $answer;
                }

                Log::warning('Gemini answer returned empty text', ['model' => $model]);
            } catch (ConnectionException $exception) {
                Log::error('Gemini answer timeout', [
                    'model' => $model,
                    'message' => $exception->getMessage(),
                ]);
            } catch (\Throwable $exception) {
                Log::error('Gemini answer error', [
                    'model' => $model,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return self::FALLBACK_NOT_FOUND;
    }

    public function generateGeneralAnswer(string $question): string
    {
        $apiKey = (string) config('services.gemini.api_key');
        $primaryModel = (string) config('services.gemini.chat_model', 'gemini-1.5-flash');

        if ($apiKey === '') {
            return '';
        }

        $systemPrompt = "Eres un asistente corporativo de NovaRetail. Responde en español de forma breve, profesional y útil.";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt."\n\nPregunta: ".$question],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.5,
                'topP' => 0.95,
                'maxOutputTokens' => 400,
            ],
        ];

        $modelsToTry = array_values(array_unique([
            $primaryModel,
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-flash-latest',
        ]));

        foreach ($modelsToTry as $model) {
            try {
                $response = Http::timeout(40)
                    ->retry(2, 500)
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", $payload)
                    ->throw();

                $answer = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));

                if ($answer !== '') {
                    return $answer;
                }
            } catch (\Throwable $exception) {
                Log::error('Gemini general answer error', [
                    'model' => $model,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return '';
    }
}
