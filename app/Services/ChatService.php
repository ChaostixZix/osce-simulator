<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Session;
use App\Models\SystemLog;

class ChatService
{
    private AIService $aiService;
    private const MAX_HISTORY_LENGTH = 10;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Process a chat message and generate AI response
     */
    public function processMessage(string $sessionId, string $content): array
    {
        $session = Session::bySessionId($sessionId)->first();
        if (!$session) {
            throw new \Exception('Session not found');
        }

        // Save user message
        $userMessage = ChatMessage::create([
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => $content
        ]);

        // Update session chat count
        $session->incrementChatMessages();

        try {
            // Manage chat history (summarize old messages if needed)
            $this->manageHistory($sessionId);

            // Get full context for AI
            $context = $this->getFullContext($sessionId);

            // Get AI response
            $startTime = microtime(true);
            $aiResponse = $this->aiService->chatCompletion($context, $sessionId);
            $responseTime = round((microtime(true) - $startTime) * 1000);

            if (!$aiResponse['success']) {
                throw new \Exception($aiResponse['error'] ?? 'AI request failed');
            }

            // Save AI response
            $assistantMessage = ChatMessage::create([
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => $aiResponse['content'],
                'tokens_used' => $aiResponse['usage']['total_tokens'] ?? null,
                'response_time_ms' => $responseTime,
                'metadata' => [
                    'model' => config('medical_training.ai.model'),
                    'usage' => $aiResponse['usage'] ?? null
                ]
            ]);

            return [
                'success' => true,
                'message' => $assistantMessage->content,
                'response_time_ms' => $responseTime,
                'tokens_used' => $aiResponse['usage']['total_tokens'] ?? null,
                'history_count' => ChatMessage::bySession($sessionId)->count()
            ];

        } catch (\Exception $e) {
            // Track error
            $session->trackError();
            
            SystemLog::logError(
                $sessionId,
                'Chat Service',
                'Failed to process chat message: ' . $e->getMessage(),
                [
                    'user_message' => $content,
                    'exception' => get_class($e)
                ]
            );

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get chat history for a session
     */
    public function getChatHistory(string $sessionId, int $limit = 50): array
    {
        $messages = ChatMessage::bySession($sessionId)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        return $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->getDisplayContent(),
                'timestamp' => $message->created_at->toISOString(),
                'is_summarized' => $message->is_summarized,
                'tokens_used' => $message->tokens_used,
                'response_time_ms' => $message->response_time_ms
            ];
        })->toArray();
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(string $sessionId): array
    {
        $session = Session::bySessionId($sessionId)->first();
        if (!$session) {
            return [];
        }

        $messageCount = ChatMessage::bySession($sessionId)->count();
        $summarizedCount = ChatMessage::bySession($sessionId)->where('is_summarized', true)->count();
        $averageResponseTime = ChatMessage::bySession($sessionId)
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');

        return [
            'session_id' => $sessionId,
            'duration_minutes' => $session->getDurationInMinutes(),
            'total_messages' => $messageCount,
            'chat_messages' => $session->chat_messages,
            'summarized_messages' => $summarizedCount,
            'average_response_time_ms' => round($averageResponseTime ?? 0),
            'error_count' => $session->error_count,
            'is_active' => $session->isActive()
        ];
    }

    /**
     * Clear chat history for a session
     */
    public function clearHistory(string $sessionId): bool
    {
        try {
            ChatMessage::bySession($sessionId)->delete();
            
            // Reset session chat count
            $session = Session::bySessionId($sessionId)->first();
            if ($session) {
                $session->update(['chat_messages' => 0]);
            }

            SystemLog::logPerformance($sessionId, 'Chat Service', 'Chat history cleared');
            
            return true;
        } catch (\Exception $e) {
            SystemLog::logError($sessionId, 'Chat Service', 'Failed to clear history: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Export chat history as formatted text
     */
    public function exportHistory(string $sessionId, string $format = 'txt'): string
    {
        $messages = ChatMessage::bySession($sessionId)
            ->orderBy('created_at', 'asc')
            ->get();

        $session = Session::bySessionId($sessionId)->first();
        
        $export = "Chat History Export\n";
        $export .= "Session ID: {$sessionId}\n";
        $export .= "Date: " . ($session ? $session->start_time->format('Y-m-d H:i:s') : 'Unknown') . "\n";
        $export .= "Total Messages: " . count($messages) . "\n";
        $export .= str_repeat('=', 50) . "\n\n";

        foreach ($messages as $message) {
            $timestamp = $message->created_at->format('H:i:s');
            $role = ucfirst($message->role);
            $content = $message->getDisplayContent();
            
            $export .= "[{$timestamp}] {$role}: {$content}\n";
            
            if ($message->response_time_ms) {
                $export .= "   Response time: {$message->response_time_ms}ms\n";
            }
            
            $export .= "\n";
        }

        return $export;
    }

    /**
     * Manage chat history with summarization
     */
    private function manageHistory(string $sessionId): void
    {
        $messageCount = ChatMessage::bySession($sessionId)->count();
        
        if ($messageCount <= self::MAX_HISTORY_LENGTH) {
            return;
        }

        // Get oldest 6 messages to summarize
        $oldMessages = ChatMessage::bySession($sessionId)
            ->unsummarized()
            ->oldest(6)
            ->get();

        if ($oldMessages->count() < 6) {
            return;
        }

        // Prepare messages for summarization
        $messagesToSummarize = $oldMessages->map(function ($msg) {
            return [
                'role' => $msg->role,
                'content' => $msg->content
            ];
        })->toArray();

        // Get summary from AI
        $summaryResult = $this->aiService->summarizeMessages($messagesToSummarize, $sessionId);
        
        $summary = $summaryResult['success'] 
            ? $summaryResult['summary']
            : 'Percakapan sebelumnya membahas berbagai topik.';

        // Mark messages as summarized
        foreach ($oldMessages as $message) {
            $message->markAsSummarized($summary);
        }

        SystemLog::logPerformance(
            $sessionId,
            'Chat Service',
            'Summarized ' . $oldMessages->count() . ' old messages',
            ['summary_length' => strlen($summary)]
        );
    }

    /**
     * Get full context including summaries and recent messages
     */
    private function getFullContext(string $sessionId): array
    {
        $messages = [];

        // Get summarized messages (use summary as system message)
        $summarizedMessages = ChatMessage::bySession($sessionId)
            ->where('is_summarized', true)
            ->where('summary', '!=', null)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($summarizedMessages->isNotEmpty()) {
            $summaries = $summarizedMessages->pluck('summary')->unique()->toArray();
            $combinedSummary = implode(' ', $summaries);
            
            $messages[] = [
                'role' => 'system',
                'content' => "Konteks percakapan sebelumnya: {$combinedSummary}"
            ];
        }

        // Get recent unsummarized messages
        $recentMessages = ChatMessage::bySession($sessionId)
            ->unsummarized()
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($recentMessages as $message) {
            $messages[] = [
                'role' => $message->role,
                'content' => $message->content
            ];
        }

        return $messages;
    }
}