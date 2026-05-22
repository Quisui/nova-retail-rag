<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Document;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'documents_total' => Document::query()->count(),
            'documents_processed' => Document::query()->where('status', 'processed')->count(),
            'documents_failed' => Document::query()->where('status', 'failed')->count(),
            'messages_total' => ChatMessage::query()->count(),
        ];

        $recentDocuments = Document::query()->latest()->limit(5)->get();
        $recentMessages = ChatMessage::query()->latest()->limit(5)->get();

        return view('dashboard.index', compact('stats', 'recentDocuments', 'recentMessages'));
    }
}
