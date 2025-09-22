<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service for generating patient visualizations using Google Gemini 2.5 Flash Image Preview.
 * Creates AI-generated medical training imagery using the real Gemini image generation API.
 */
class PatientVisualizerService
{
    private string $apiKey;
    private string $model = 'gemini-2.5-flash-image-preview';
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');

        if (empty($this->apiKey)) {
            throw new \Exception('Gemini API key not configured. Please set GEMINI_API_KEY in your .env file.');
        }
    }

    /**
     * Generate a patient visualization image using Gemini 2.5 Flash Image Preview
     */
    public function generateVisualization(string $prompt, array $options = []): array
    {
        try {
            // Enhance the prompt with medical training context
            $enhancedPrompt = $this->enhancePromptForMedicalTraining($prompt, $options);

            Log::info('Generating patient visualization with Gemini AI', [
                'original_prompt' => $prompt,
                'enhanced_prompt' => $enhancedPrompt,
                'model' => $this->model,
                'options' => $options
            ]);

            $requestBody = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $enhancedPrompt,
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 2048,
                    'responseModalities' => [
                        'IMAGE',
                        'TEXT',
                    ],
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ]
                ]
            ];

            $response = Http::timeout(120)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}", $requestBody);

            Log::info('Gemini API Response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body_length' => strlen($response->body())
            ]);

            if (!$response->successful()) {
                Log::error('Gemini image generation failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'prompt' => $enhancedPrompt
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to generate visualization',
                    'details' => $response->json(),
                    'status_code' => $response->status()
                ];
            }

            $data = $response->json();

            // Check for safety blocks
            if (isset($data['promptFeedback']['safetyRatings'])) {
                foreach ($data['promptFeedback']['safetyRatings'] as $rating) {
                    if (isset($rating['blocked']) && $rating['blocked']) {
                        Log::warning('Content blocked by safety filters', [
                            'prompt' => $enhancedPrompt,
                            'safety_rating' => $rating
                        ]);

                        return [
                            'success' => false,
                            'error' => 'Content blocked by safety filters',
                            'blocked_category' => $rating['category'] ?? 'Unknown'
                        ];
                    }
                }
            }

            // Extract the generated content
            $candidates = $data['candidates'] ?? [];
            if (empty($candidates)) {
                return [
                    'success' => false,
                    'error' => 'No candidates generated',
                    'response' => $data
                ];
            }

            $candidate = $candidates[0];
            $parts = $candidate['content']['parts'] ?? [];

            // Find inline data (base64 image)
            $imageData = null;
            $mimeType = null;

            foreach ($parts as $part) {
                if (isset($part['inlineData'])) {
                    $imageData = $part['inlineData']['data'];
                    $mimeType = $part['inlineData']['mimeType'];
                    Log::info('Found image data', [
                        'mime_type' => $mimeType,
                        'data_length' => strlen($imageData)
                    ]);
                    break;
                }
            }

            if (!$imageData) {
                Log::warning('No image data found in response', [
                    'candidates' => $candidates,
                    'parts' => $parts
                ]);

                return [
                    'success' => false,
                    'error' => 'No image data in response',
                    'response' => $data
                ];
            }

            // Save the generated image
            $savedImage = $this->saveImage($imageData, $mimeType, $prompt);

            Log::info('Successfully generated and saved patient visualization', [
                'image_url' => $savedImage['url'],
                'mime_type' => $mimeType,
                'prompt' => $prompt
            ]);

            return [
                'success' => true,
                'image_url' => $savedImage['url'],
                'image_path' => $savedImage['path'],
                'prompt' => $prompt,
                'enhanced_prompt' => $enhancedPrompt,
                'mime_type' => $mimeType,
                'generated_at' => now(),
                'type' => 'gemini_ai_generated',
                'watermarked' => true // Gemini automatically adds SynthID
            ];

        } catch (\Exception $e) {
            Log::error('Patient visualization generation error', [
                'prompt' => $prompt,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enhance the user prompt with medical training context and style guidance
     */
    private function enhancePromptForMedicalTraining(string $userPrompt, array $options = []): string
    {
        $enhancedPrompt = sprintf(
            "Create a professional medical illustration showing: %s. " .
            "Style: Clean, medical training material appropriate for healthcare education. " .
            "Requirements: " .
            "- Professional medical photography style " .
            "- Appropriate for educational use " .
            "- Non-graphic and suitable for medical students " .
            "- Clear, well-lit clinical setting " .
            "- Patient appears comfortable and dignified " .
            "- Include subtle medical context elements " .
            "- High quality, realistic representation " .
            "- Safe for work and educational purposes",
            $userPrompt
        );

        return $enhancedPrompt;
    }

    /**
     * Save generated image to storage
     */
    private function saveImage(string $base64Data, string $mimeType, string $originalPrompt): array
    {
        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg'
        };

        // Create filename with hash of prompt for caching
        $promptHash = md5($originalPrompt);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "patient-visualizations/gemini_{$promptHash}_{$timestamp}.{$extension}";

        // Decode base64 and save
        $imageContent = base64_decode($base64Data);

        // Use public disk to make images accessible
        $disk = Storage::disk('public');

        // Ensure directory exists
        $directory = dirname($filename);
        if (!$disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $saved = $disk->put($filename, $imageContent);

        if (!$saved) {
            throw new \Exception('Failed to save generated image');
        }

        Log::info('Image saved successfully', [
            'filename' => $filename,
            'size' => strlen($imageContent),
            'extension' => $extension
        ]);

        return [
            'path' => $filename,
            'url' => $disk->url($filename)
        ];
    }

    /**
     * Get cached visualization or generate new one
     */
    public function getCachedOrGenerate(string $prompt, array $options = []): array
    {
        // Always generate new for now (can add caching later if needed)
        return $this->generateVisualization($prompt, $options);
    }

    /**
     * Get common medical scenario prompts for quick generation
     */
    public function getCommonPrompts(): array
    {
        return [
            'chest-pain' => [
                'prompt' => 'Adult male patient experiencing acute chest pain, sitting upright, hand on chest, in emergency department',
                'description' => 'Acute Chest Pain Patient',
                'category' => 'cardiology'
            ],
            'dyspnea' => [
                'prompt' => 'Adult female patient with difficulty breathing, using accessory muscles, seated position in clinical setting',
                'description' => 'Dyspnea Patient',
                'category' => 'pulmonology'
            ],
            'abdominal-pain' => [
                'prompt' => 'Adult patient with severe abdominal pain, lying on examination table, guarding posture',
                'description' => 'Abdominal Pain Patient',
                'category' => 'gastroenterology'
            ],
            'headache' => [
                'prompt' => 'Adult patient with severe headache, holding head, photophobic, in consultation room',
                'description' => 'Headache Patient',
                'category' => 'neurology'
            ],
            'elderly-frail' => [
                'prompt' => 'Elderly patient in hospital bed, with medical monitoring equipment, family member present',
                'description' => 'Elderly Hospitalized Patient',
                'category' => 'geriatrics'
            ],
            'pediatric' => [
                'prompt' => 'Child patient with fever, sitting on examination table, parent nearby, comfortable clinical setting',
                'description' => 'Pediatric Patient',
                'category' => 'pediatrics'
            ]
        ];
    }
}