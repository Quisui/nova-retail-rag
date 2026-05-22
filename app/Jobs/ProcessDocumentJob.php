<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\RagService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    public function __construct(public int $documentId)
    {
    }

    public function handle(RagService $ragService): void
    {
        $document = Document::query()->find($this->documentId);

        if (! $document) {
            return;
        }

        $ragService->ingestDocument($document);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDocumentJob failed', [
            'document_id' => $this->documentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
