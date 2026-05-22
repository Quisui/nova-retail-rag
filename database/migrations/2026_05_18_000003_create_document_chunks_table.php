<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->longText('content');
            $table->json('embedding')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'chunk_index']);
            $table->index('document_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE document_chunks DROP COLUMN embedding;');
            DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding vector(768);');
            DB::statement('CREATE INDEX document_chunks_embedding_idx ON document_chunks USING ivfflat (embedding vector_l2_ops) WITH (lists = 100);');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
