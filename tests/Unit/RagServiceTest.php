<?php

namespace Tests\Unit;

use App\Repositories\DocumentChunkRepository;
use App\Services\DocumentProcessorService;
use App\Services\GeminiService;
use App\Services\RagService;
use Mockery;
use PHPUnit\Framework\TestCase;

class RagServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_cosine_similarity_returns_expected_value(): void
    {
        $service = new RagService(
            Mockery::mock(GeminiService::class),
            new DocumentProcessorService(),
            Mockery::mock(DocumentChunkRepository::class),
        );

        $sim = $service->cosineSimilarity([1, 0, 1], [1, 0, 1]);

        $this->assertEqualsWithDelta(1.0, $sim, 0.0001);
    }
}
