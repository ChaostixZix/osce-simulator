<?php

$apiKey = 'AIzaSyAVxjuSr7YoFDINEwlMDNsCw1HpeHFHf88';
$model = 'gemini-2.5-flash'; // The problematic model mentioned by user

echo "=== SPECIFIC GEMINI MODEL TEST ===\n\n";
echo "Testing model: $model\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Test the exact prompt structure used by the AI Assessment system
$complexPrompt = 'You are an experienced physician examiner conducting an OSCE assessment. You must analyze the complete OSCE session data and provide detailed area-by-area assessment with AI-determined scoring based on medical education best practices.

CRITICAL: YOU MUST ANALYZE EACH CLINICAL AREA SEPARATELY AND ASSIGN SCORES BASED ON EVIDENCE.

Your Role:
- Expert medical educator and examiner
- Focus on clinical competency and patient safety
- Consider the specific case context and learning objectives
- Provide detailed educational feedback with specific evidence and citations

Rules:
- Output MUST be a single JSON object and nothing else
- Analyze EACH clinical area separately with detailed justification
- Assign scores based on evidence from the session data
- Provide extensive citations referencing specific actions, messages, tests, examinations
- Use professional language with comprehensive analysis and specific examples
- Be conservative: do not infer beyond the artifact; flag unsafe or missing steps

Session Data to Analyze:
{
  "session_id": 999,
  "assessment_type": "holistic_session_assessment",
  "case": {
    "title": "Chest Pain Assessment",
    "chief_complaint": "Chest pain for 2 hours"
  },
  "transcript": [
    {
      "id": 1,
      "sender_type": "user", 
      "text": "Tell me about your chest pain"
    }
  ],
  "actions": {
    "tests": [
      {
        "test_name": "ECG",
        "cost": 50,
        "result": "Normal sinus rhythm"
      }
    ],
    "examinations": [
      {
        "examination_type": "Cardiac",
        "finding": "Regular rate and rhythm"
      }
    ]
  }
}

Return JSON matching this exact schema:

{
  "total_score": number,
  "max_possible_score": 100,
  "assessment_type": "detailed_clinical_areas_assessment",
  "clinical_areas": [
    {
      "area": "History-Taking",
      "key": "history",
      "score": number,
      "max_score": 20,
      "justification": string,
      "citations": [],
      "strengths": [],
      "areas_for_improvement": []
    }
  ],
  "overall_feedback": string,
  "safety_concerns": [],
  "recommendations": [],
  "model_info": {
    "name": string,
    "temperature": number,
    "assessment_approach": "detailed_areas_analysis"
  }
}';

// Test 1: Basic model availability
echo "1. TESTING MODEL AVAILABILITY\n";
$basicPayload = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Say "Model working" and nothing else.']
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0,
        'maxOutputTokens' => 50,
    ]
];

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($basicPayload));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

if ($error) {
    echo "❌ Curl error: $error\n";
    exit(1);
} elseif ($httpCode !== 200) {
    echo "❌ Model not available (HTTP $httpCode)\n";
    echo "Response: $response\n";
    
    // Try alternative model
    echo "\n2. TRYING ALTERNATIVE MODEL (gemini-1.5-flash)\n";
    $altModel = 'gemini-1.5-flash';
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/{$altModel}:generateContent?key={$apiKey}");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($basicPayload));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode === 200) {
        echo "✅ Alternative model works - Issue is with gemini-2.5-flash!\n";
        echo "SOLUTION: Update .env to use GEMINI_MODEL=gemini-1.5-flash\n";
        $model = $altModel; // Use working model for further testing
    } else {
        echo "❌ Alternative model also failed\n";
        exit(1);
    }
} else {
    echo "✅ Model available\n";
}

// Test 2: Complex prompt with JSON schema
echo "\n2. TESTING COMPLEX ASSESSMENT PROMPT\n";

$assessmentPayload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $complexPrompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.1,
        'topK' => 1,
        'topP' => 1,
        'maxOutputTokens' => 8000,
        'responseMimeType' => 'application/json',
    ]
];

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($assessmentPayload));
curl_setopt($curl, CURLOPT_TIMEOUT, 60); // Longer timeout for complex request

$startTime = microtime(true);
$response = curl_exec($curl);
$endTime = microtime(true);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

echo "Response time: " . round($endTime - $startTime, 2) . " seconds\n";
echo "HTTP Status: $httpCode\n";

if ($error) {
    echo "❌ Curl error: $error\n";
} elseif ($httpCode !== 200) {
    echo "❌ Complex prompt failed\n";
    echo "Response: $response\n";
    
    // Parse error details
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['error'])) {
        echo "\nDetailed error:\n";
        echo "Code: " . ($errorData['error']['code'] ?? 'unknown') . "\n";
        echo "Message: " . ($errorData['error']['message'] ?? 'unknown') . "\n";
        echo "Status: " . ($errorData['error']['status'] ?? 'unknown') . "\n";
        
        // Common error solutions
        if (isset($errorData['error']['code'])) {
            switch($errorData['error']['code']) {
                case 400:
                    echo "\nPossible causes:\n";
                    echo "- Prompt too long or complex\n";
                    echo "- Invalid JSON schema specification\n";
                    echo "- Model doesn't support responseMimeType parameter\n";
                    break;
                case 429:
                    echo "\nRate limiting - wait and retry\n";
                    break;
                case 500:
                case 503:
                    echo "\nGoogle server error - try again later\n";
                    break;
            }
        }
    }
} else {
    echo "✅ Complex prompt successful\n";
    $data = json_decode($response, true);
    
    if ($data && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $data['candidates'][0]['content']['parts'][0]['text'];
        echo "Response length: " . strlen($text) . " characters\n";
        
        // Try to parse JSON
        $jsonData = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ Valid JSON returned\n";
            echo "Total score: " . ($jsonData['total_score'] ?? 'missing') . "\n";
            echo "Assessment type: " . ($jsonData['assessment_type'] ?? 'missing') . "\n";
            echo "Clinical areas: " . (isset($jsonData['clinical_areas']) ? count($jsonData['clinical_areas']) : 'missing') . "\n";
            echo "✅ ALL TESTS PASSED - AI Assessment should work!\n";
        } else {
            echo "❌ Invalid JSON returned\n";
            echo "JSON error: " . json_last_error_msg() . "\n";
            echo "First 500 chars of response:\n" . substr($text, 0, 500) . "\n";
        }
    } else {
        echo "❌ No content in response\n";
        echo "Full response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n=== DIAGNOSIS ===\n";
if ($httpCode === 200) {
    echo "✅ Gemini API is working correctly\n";
    echo "The issue is likely in the Laravel application logic, not the API\n";
    echo "\nRecommendations:\n";
    echo "1. Check Laravel logs during assessment\n";
    echo "2. Verify session data structure\n";
    echo "3. Test with a real OSCE session\n";
} else {
    echo "❌ Gemini API has issues with the current model\n";
    echo "Recommendation: Switch to gemini-1.5-flash in .env\n";
}

echo "\n=== TEST COMPLETE ===\n";