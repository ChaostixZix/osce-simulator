<?php
// Simple test script for Gemini API without Laravel bootstrap complexity

$apiKey = 'AIzaSyAVxjuSr7YoFDINEwlMDNsCw1HpeHFHf88';
$model = 'gemini-2.5-flash';

echo "=== GEMINI API DIRECT TEST ===\n\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Model: $model\n\n";

// Test 1: Simple connection test
echo "1. TESTING SIMPLE CONNECTION\n";

$testPayload = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Say "Hello, I am working!" and nothing else.']
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0,
        'topK' => 1,
        'topP' => 1,
        'maxOutputTokens' => 100,
    ]
];

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($testPayload));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

echo "HTTP Status: $httpCode\n";

if ($error) {
    echo "❌ Curl error: $error\n\n";
} elseif ($httpCode === 200) {
    echo "✅ Simple connection successful\n";
    $data = json_decode($response, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No text returned';
    echo "Response: $text\n\n";
} else {
    echo "❌ Simple connection failed\n";
    echo "Response: $response\n\n";
}

// Test 2: Mock assessment with JSON schema
echo "2. TESTING MOCK ASSESSMENT WITH JSON SCHEMA\n";

$assessmentPayload = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'You are testing an AI assessment system. Return a JSON object with these exact fields:
{
  "total_score": 75,
  "max_possible_score": 100,
  "assessment_type": "detailed_clinical_areas_assessment",
  "clinical_areas": [
    {
      "area": "History-Taking",
      "key": "history",
      "score": 15,
      "max_score": 20,
      "justification": "Good systematic approach to history taking with relevant questions about chest pain characteristics.",
      "citations": ["msg#1: asked about onset", "msg#3: inquired about character"],
      "strengths": ["Systematic approach", "Relevant questions"],
      "areas_for_improvement": ["Could explore family history more"]
    }
  ],
  "overall_feedback": "This is a test assessment showing the expected JSON structure for the AI assessment system.",
  "safety_concerns": [],
  "recommendations": ["Continue practicing systematic history taking"],
  "model_info": {
    "name": "gemini-2.5-flash",
    "temperature": 0.1,
    "assessment_approach": "detailed_areas_analysis"
  }
}

Return ONLY the JSON object, nothing else.'
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.1,
        'topK' => 1,
        'topP' => 1,
        'maxOutputTokens' => 2000,
        'responseMimeType' => 'application/json'
    ]
];

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($assessmentPayload));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

echo "HTTP Status: $httpCode\n";

if ($error) {
    echo "❌ Curl error: $error\n\n";
} elseif ($httpCode === 200) {
    echo "✅ Assessment test successful\n";
    $data = json_decode($response, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No text returned';
    
    echo "Raw response length: " . strlen($text) . " characters\n";
    echo "Response preview: " . substr($text, 0, 200) . "...\n";
    
    // Try to parse as JSON
    $jsonData = json_decode($text, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ Valid JSON returned\n";
        echo "Total score: " . ($jsonData['total_score'] ?? 'missing') . "\n";
        echo "Assessment type: " . ($jsonData['assessment_type'] ?? 'missing') . "\n";
        echo "Clinical areas count: " . (isset($jsonData['clinical_areas']) ? count($jsonData['clinical_areas']) : 'missing') . "\n";
    } else {
        echo "❌ Invalid JSON returned\n";
        echo "JSON error: " . json_last_error_msg() . "\n";
        echo "Full response:\n$text\n";
    }
} else {
    echo "❌ Assessment test failed\n";
    echo "Response: $response\n";
    
    // Parse error details if available
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['error'])) {
        echo "\nError details:\n";
        echo "Code: " . ($errorData['error']['code'] ?? 'unknown') . "\n";
        echo "Message: " . ($errorData['error']['message'] ?? 'unknown') . "\n";
        echo "Status: " . ($errorData['error']['status'] ?? 'unknown') . "\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";