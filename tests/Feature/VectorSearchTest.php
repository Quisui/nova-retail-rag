<?php

namespace Tests\Feature;

use App\DTOs\ChunkData;
use App\Models\Document;
use App\Repositories\DocumentChunkRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VectorSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_can_search_similar_chunks(): void
    {
        $document = Document::query()->create([
            'title' => 'Doc',
            'filename' => 'doc.md',
            'path' => 'documents/doc.md',
            'mime_type' => 'text/markdown',
            'status' => 'processed',
        ]);

        $repository = app(DocumentChunkRepository::class);
        $repository->replaceChunksForDocument($document, [
            ['chunk' => new ChunkData(0, 'Política devoluciones', []), 'embedding' => [1, 0, 0]],
            ['chunk' => new ChunkData(1, 'Procedimiento logístico', []), 'embedding' => [0, 1, 0]],
        ]);

        $result = $repository->searchByEmbedding([1, 0, 0], 1);

        $this->assertCount(1, $result);
        $this->assertSame('Política devoluciones', $result[0]['content']);
    }
}
