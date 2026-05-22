<?php

namespace Tests\Unit;

use App\Services\DocumentProcessorService;
use PHPUnit\Framework\TestCase;

class DocumentProcessorServiceTest extends TestCase
{
    public function test_clean_and_split_text(): void
    {
        $service = new DocumentProcessorService();

        $text = "Linea 1\r\n\r\n\r\nLinea 2\t\tcon espacios";
        $clean = $service->cleanText($text);

        $this->assertStringContainsString("Linea 1", $clean);
        $this->assertStringContainsString("Linea 2 con espacios", $clean);

        $chunks = $service->splitIntoChunks(str_repeat('palabra ', 1200), 200, 50);

        $this->assertNotEmpty($chunks);
        $this->assertGreaterThan(1, count($chunks));
    }
}
