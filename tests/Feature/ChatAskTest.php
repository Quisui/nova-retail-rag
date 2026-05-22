<?php

namespace Tests\Feature;

use App\DTOs\ChatAnswerData;
use App\Services\RagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ChatAskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_ask_and_store_chat_message(): void
    {
        $mock = Mockery::mock(RagService::class);
        $mock->shouldReceive('answerQuestion')
            ->once()
            ->andReturn(new ChatAnswerData(
                answer: 'Respuesta de prueba',
                contextUsed: 'Contexto usado de prueba',
                sources: [['title' => 'Doc 1', 'chunk_index' => 0, 'distance' => 0.1]],
                chunks: [['content' => 'Chunk de prueba', 'document_title' => 'Doc 1', 'chunk_index' => 0, 'distance' => 0.1]],
            ));

        $this->app->instance(RagService::class, $mock);

        $response = $this->post(route('chat.ask'), [
            'question' => '¿Cuál es la política de devoluciones?',
        ]);

        $response->assertRedirect(route('chat.index'));

        $this->assertDatabaseHas('chat_messages', [
            'question' => '¿Cuál es la política de devoluciones?',
            'answer' => 'Respuesta de prueba',
        ]);
    }
}
