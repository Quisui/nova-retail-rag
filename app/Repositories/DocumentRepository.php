<?php

namespace App\Repositories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;

class DocumentRepository
{
    public function latestWithChunkCount(int $limit = 50): Collection
    {
        return Document::query()
            ->withCount('chunks')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function byIdWithChunks(int $id): Document
    {
        return Document::query()->with('chunks')->findOrFail($id);
    }
}
