<?php

namespace App\Repositories;

use App\DTOs\ChunkData;
use App\Models\Document;
use App\Support\PgVectorHelper;
use Illuminate\Support\Facades\DB;

class DocumentChunkRepository
{
    public function replaceChunksForDocument(Document $document, array $chunksWithEmbeddings): void
    {
        DB::transaction(function () use ($document, $chunksWithEmbeddings): void {
            DB::table('document_chunks')->where('document_id', $document->id)->delete();

            foreach ($chunksWithEmbeddings as $item) {
                $chunk = $item['chunk'];
                $embedding = $item['embedding'];

                if (! $chunk instanceof ChunkData) {
                    continue;
                }

                if (DB::getDriverName() === 'pgsql') {
                    DB::insert(
                        'INSERT INTO document_chunks (document_id, chunk_index, content, embedding, metadata, created_at, updated_at)
                        VALUES (?, ?, ?, ?::vector, ?::jsonb, NOW(), NOW())',
                        [
                            $document->id,
                            $chunk->index,
                            $chunk->content,
                            PgVectorHelper::arrayToVectorLiteral($embedding),
                            json_encode($chunk->metadata, JSON_THROW_ON_ERROR),
                        ]
                    );
                } else {
                    DB::table('document_chunks')->insert([
                        'document_id' => $document->id,
                        'chunk_index' => $chunk->index,
                        'content' => $chunk->content,
                        'embedding' => json_encode($embedding, JSON_THROW_ON_ERROR),
                        'metadata' => json_encode($chunk->metadata, JSON_THROW_ON_ERROR),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function searchByEmbedding(array $queryEmbedding, int $limit = 5): array
    {
        if (DB::getDriverName() !== 'pgsql') {
            return $this->searchByEmbeddingFallback($queryEmbedding, $limit);
        }

        $vectorLiteral = PgVectorHelper::arrayToVectorLiteral($queryEmbedding);

        $rows = DB::select(
            'SELECT dc.id, dc.document_id, dc.chunk_index, dc.content, dc.metadata,
                    d.title as document_title, d.filename,
                    (dc.embedding <-> ?::vector) as distance
             FROM document_chunks dc
             INNER JOIN documents d ON d.id = dc.document_id
             ORDER BY dc.embedding <-> ?::vector
             LIMIT ?',
            [$vectorLiteral, $vectorLiteral, $limit]
        );

        return array_map(static function (object $row): array {
            return [
                'id' => (int) $row->id,
                'document_id' => (int) $row->document_id,
                'chunk_index' => (int) $row->chunk_index,
                'content' => $row->content,
                'metadata' => $row->metadata ? json_decode($row->metadata, true) : [],
                'document_title' => $row->document_title,
                'filename' => $row->filename,
                'distance' => (float) $row->distance,
            ];
        }, $rows);
    }

    private function searchByEmbeddingFallback(array $queryEmbedding, int $limit): array
    {
        $rows = DB::table('document_chunks as dc')
            ->join('documents as d', 'd.id', '=', 'dc.document_id')
            ->select([
                'dc.id',
                'dc.document_id',
                'dc.chunk_index',
                'dc.content',
                'dc.embedding',
                'dc.metadata',
                'd.title as document_title',
                'd.filename',
            ])
            ->get();

        $scored = $rows->map(function (object $row) use ($queryEmbedding): array {
            $embedding = is_string($row->embedding) ? (json_decode($row->embedding, true) ?: []) : (array) $row->embedding;
            $similarity = $this->cosineSimilarity($queryEmbedding, array_map('floatval', $embedding));

            return [
                'id' => (int) $row->id,
                'document_id' => (int) $row->document_id,
                'chunk_index' => (int) $row->chunk_index,
                'content' => $row->content,
                'metadata' => $row->metadata ? json_decode($row->metadata, true) : [],
                'document_title' => $row->document_title,
                'filename' => $row->filename,
                'distance' => 1 - $similarity,
            ];
        })->sortBy('distance')->take($limit)->values();

        return $scored->all();
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b) || $a === []) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $value) {
            $av = (float) $value;
            $bv = (float) ($b[$i] ?? 0.0);
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
