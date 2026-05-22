<?php

namespace App\Repositories;

use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Collection;

class ChatMessageRepository
{
    public function latest(int $limit = 50): Collection
    {
        return ChatMessage::query()->latest()->limit($limit)->get();
    }

    public function create(array $data): ChatMessage
    {
        return ChatMessage::query()->create($data);
    }
}
