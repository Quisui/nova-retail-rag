<?php

namespace App\Services;

use App\DTOs\ChunkData;
use RuntimeException;

class DocumentProcessorService
{
    public function extractText(string $path, string $mimeType): string
    {
        return match ($mimeType) {
            'text/plain', 'text/markdown', 'text/x-markdown' => (string) file_get_contents($path),
            'application/pdf' => $this->extractPdfText($path),
            default => throw new RuntimeException("Tipo de documento no soportado: {$mimeType}"),
        };
    }

    public function cleanText(string $text): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $normalized = preg_replace('/\p{C}+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/[ \t]+/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\n{3,}/', "\n\n", $normalized) ?? $normalized;

        return trim($normalized);
    }

    public function normalizeText(string $text): string
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        return trim($text);
    }

    public function splitIntoChunks(string $text, int $chunkSize = 800, int $overlap = 120): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];

        if ($words === []) {
            return [];
        }

        $chunks = [];
        $step = max(1, $chunkSize - $overlap);
        $index = 0;

        for ($i = 0; $i < count($words); $i += $step) {
            $slice = array_slice($words, $i, $chunkSize);
            if ($slice === []) {
                continue;
            }

            $content = trim(implode(' ', $slice));
            if ($content === '') {
                continue;
            }

            $chunks[] = new ChunkData($index, $content, [
                'word_start' => $i,
                'word_end' => $i + count($slice) - 1,
            ]);

            $index++;
        }

        return $chunks;
    }

    private function extractPdfText(string $path): string
    {
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);
            return $pdf->getText();
        }

        $command = sprintf('pdftotext %s -', escapeshellarg($path));
        $output = shell_exec($command);

        if (! is_string($output) || trim($output) === '') {
            throw new RuntimeException('No se pudo extraer texto de PDF. Instala pdftotext o smalot/pdfparser.');
        }

        return $output;
    }
}
