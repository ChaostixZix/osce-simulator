<?php

namespace App\Services;

use App\Models\OsceCase;

class LocalPatientService
{
    public function reply(OsceCase $case, string $question): array
    {
        $text = mb_strtolower($question);
        $patterns = [
            'radiation' => '/\b(radiat\w*|spread\w*|travel\w*|arm|jaw|where.*pain)\b/u',
            'onset' => '/\b(start\w*|began|begin\w*|onset|when|how long|sudden\w*)\b/u',
            'history' => '/\b(history|previous\w*|condition\w*|blood pressure|cholesterol|hypertension)\b/u',
            'medication' => '/\b(medic\w*|drug\w*|tablet\w*|inhaler\w*)\b/u',
            'breathing' => '/\b(breath\w*|wheez\w*)\b/u',
            'pain' => '/\b(pain|hurt\w*|feel\w*|discomfort|character\w*|complaint\w*)\b/u',
        ];

        // More specific topics precede general pain words.
        foreach ($patterns as $topic => $pattern) {
            if (preg_match($pattern, $text) && isset($case->ai_patient_responses[$topic])) {
                return ['message' => $case->ai_patient_responses[$topic], 'topic' => $topic];
            }
        }

        if (preg_match('/\b(name|old|age|occupation|work|who are you)\b/u', $text)) {
            $identity = explode('.', $case->ai_patient_profile ?? '')[0];
            if ($identity !== '') {
                return ['message' => $identity.'.', 'topic' => 'profile'];
            }
        }

        if (preg_match('/\b(smok\w*|tobacco)\b/u', $text)) {
            foreach (explode('.', $case->ai_patient_profile ?? '') as $fact) {
                if (preg_match('/smok/i', $fact)) {
                    return ['message' => trim($fact).'.', 'topic' => 'smoking'];
                }
            }
        }

        if (preg_match('/\b(symptom\w*|anything else|other problems|nause\w*|sweat\w*)\b/u', $text) && $case->ai_patient_symptoms) {
            return ['message' => implode('; ', $case->ai_patient_symptoms).'.', 'topic' => 'symptoms'];
        }

        if (preg_match('/\b(hello|hi|good morning|good afternoon|my name|introduce)\b/u', $text)) {
            return ['message' => 'Hello, doctor.', 'topic' => 'greeting'];
        }

        return [
            'message' => 'That detail is not recorded in this case. Please ask about my symptoms or medical history.',
            'topic' => 'unrecorded',
        ];
    }
}
