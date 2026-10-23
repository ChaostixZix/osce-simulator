<?php

namespace App\Services;

use App\Models\OsceSession;

class EvidenceReviewService
{
    public function create(OsceSession $session): array
    {
        $messages = $session->chatMessages()->orderBy('id')->get();
        $topics = [
            'pain' => 'Pain characteristics',
            'radiation' => 'Spread of pain',
            'onset' => 'Onset of symptoms',
            'history' => 'Medical history',
            'smoking' => 'Smoking history',
            'symptoms' => 'Associated symptoms',
        ];
        $observations = [];
        foreach ($topics as $topic => $label) {
            $response = $messages->first(fn ($message) => $message->sender_type === 'ai_patient' && $message->source_topic === $topic);
            $question = $response ? $messages->last(fn ($message) => $message->sender_type === 'user' && $message->id < $response->id) : null;
            $observations[] = [
                'topic' => $topic,
                'label' => $label,
                'recorded' => (bool) $response,
                'question' => $question?->message,
                'response' => $response?->message,
            ];
        }

        return [
            'kind' => 'evidence_review',
            'version' => 1,
            'reviewed_at' => now()->toISOString(),
            'observations' => $observations,
            'actions' => $session->guest_actions ?? [],
            'checklist' => $session->osceCase->checklist ?? [],
            'summary' => [
                'diagnosis' => $session->diagnosis,
                'differential_diagnosis' => $session->differential_diagnosis,
                'plan' => $session->plan,
            ],
            'message_count' => $messages->where('sender_type', 'user')->count(),
            'unknown_count' => $messages->where('source_topic', 'unrecorded')->count(),
            'limitations' => 'This review identifies recorded discussion topics and actions. It does not judge diagnostic accuracy, examination technique, treatment safety or clinical competence. An examiner must review those skills using the original checklist.',
        ];
    }
}
