<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_document_and_dispatch_processing_job(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->createWithContent('policy.md', "# Policy\nNovaRetail rules.");

        $response = $this->post(route('documents.store'), [
            'title' => 'Policy Test',
            'document' => $file,
        ]);

        $response->assertRedirect(route('documents.index'));

        $this->assertDatabaseHas('documents', [
            'title' => 'Policy Test',
            'status' => 'pending',
        ]);

        $this->assertTrue(Document::query()->exists());
        Queue::assertPushed(ProcessDocumentJob::class);
    }
}
