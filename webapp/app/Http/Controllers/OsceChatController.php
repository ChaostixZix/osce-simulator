<?php

namespace App\Http\Controllers;

use App\Models\OsceSession;
use App\Models\OsceChatMessage;
use App\Services\AiPatientService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OsceChatController extends Controller
{
	public function __construct(
		private AiPatientService $aiPatientService
	) {}

	public function sendMessage(Request $request): JsonResponse
	{
		$request->validate([
			'session_id' => 'required|exists:osce_sessions,id',
			'message' => 'required|string|max:1000'
		]);

		try {
			$session = OsceSession::with('osceCase')
				->where('id', $request->session_id)
				->where('user_id', Auth::id())
				->firstOrFail();

            // Check if session is active (not expired and in progress)
            if (!$session->isActive()) {
                if ($session->is_expired) {
                    $session->markAsCompleted();
                }
                return response()->json([
                    'error' => 'Session is not active',
                    'time_status' => $session->time_status,
                ], 400);
            }

			// Save user message (persist immediately for durability)
			$userMessage = OsceChatMessage::create([
				'osce_session_id' => $session->id,
				'sender_type' => 'user',
				'message' => $request->message,
				'sent_at' => now()
			]);

			// Get recent chat history for context (lightweight slice)
			$chatHistory = $session->chatMessages()
				->select(['sender_type', 'message', 'sent_at'])
				->orderBy('sent_at', 'asc')
				->limit(30) // limit context to last ~30 messages for performance
				->get()
				->toArray();

			// Generate AI patient response
			$aiResponse = $this->aiPatientService->generatePatientResponse(
				$session,
				$request->message,
				$chatHistory
			);

			// Save AI response (persist immediately)
			$aiMessage = OsceChatMessage::create([
				'osce_session_id' => $session->id,
				'sender_type' => 'ai_patient',
				'message' => $aiResponse,
				'sent_at' => now()
			]);

			return response()->json([
				'success' => true,
				'user_message' => $userMessage,
				'ai_response' => $aiMessage
			]);

		} catch (\Exception $e) {
			Log::error('OSCE Chat error', [
				'message' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);

			return response()->json([
				'error' => 'Failed to process message'
			], 500);
		}
	}

	public function getChatHistory(Request $request, OsceSession $session): JsonResponse
	{
		$request->validate([
			'limit' => 'nullable|integer|min:1|max:200'
		]);

		try {
			if ($session->user_id !== Auth::id()) {
				abort(404);
			}

			$limit = (int) ($request->input('limit') ?? 50); // sensible default

			$messages = OsceChatMessage::query()
				->where('osce_session_id', $session->id)
				->orderBy('sent_at', 'asc')
				->orderBy('id', 'asc')
				->limit($limit)
				->get();

			return response()->json([
				'success' => true,
				'messages' => $messages
			]);

		} catch (\Exception $e) {
			Log::error('OSCE Chat history error', [
				'message' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);

			return response()->json([
				'error' => 'Failed to retrieve chat history'
			], 500);
		}
	}

	public function startChat(Request $request): JsonResponse
	{
		$request->validate([
			'session_id' => 'required|exists:osce_sessions,id'
		]);

		try {
			$session = OsceSession::with('osceCase')
				->where('id', $request->session_id)
				->where('user_id', Auth::id())
				->firstOrFail();

            // Check if session is active (not expired and in progress)
            if (!$session->isActive()) {
                if ($session->is_expired) {
                    $session->markAsCompleted();
                }
                return response()->json([
                    'error' => 'Session is not active',
                    'time_status' => $session->time_status,
                ], 400);
            }

			// Check if AI service is configured
			if (!$this->aiPatientService->isConfigured()) {
				return response()->json([
					'error' => 'AI service is not configured'
				], 500);
			}

			// Create initial system message only once per session
			$exists = OsceChatMessage::query()
				->where('osce_session_id', $session->id)
				->exists();

			$systemMessage = null;
			if (!$exists) {
				$systemMessage = OsceChatMessage::create([
					'osce_session_id' => $session->id,
					'sender_type' => 'system',
					'message' => "OSCE session started. You are now chatting with an AI patient for case: {$session->osceCase->title}",
					'sent_at' => now()
				]);
			}

			return response()->json([
				'success' => true,
				'session' => $session->load('osceCase'),
				'system_message' => $systemMessage
			]);

		} catch (\Exception $e) {
			Log::error('OSCE Chat start error', [
				'message' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);

			return response()->json([
				'error' => 'Failed to start chat'
			], 500);
		}
	}
}
