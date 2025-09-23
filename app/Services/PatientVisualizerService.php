<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\PatientVisualization;

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

        Log::info('PatientVisualizerService initialized', [
            'api_key_set' => !empty($this->apiKey),
            'api_key_length' => $this->apiKey ? strlen($this->apiKey) : 0,
            'model' => $this->model
        ]);

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
            $savedImage = $this->saveImage($imageData, $mimeType, $prompt, $enhancedPrompt, $options);

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
                'demographics' => $options['demographics'] ?? null,
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
        // Extract demographics from options if available
        $demographics = $options['demographics'] ?? [];
        $age = $demographics['age'] ?? 'middle-aged';
        $gender = $demographics['gender'] ?? 'patient';
        $ethnicity = $demographics['ethnicity'] ?? 'Southeast Asian';
        
        // Determine clinical setting based on prompt or options
        $isEmergency = stripos($userPrompt, 'emergency') !== false || 
                      stripos($userPrompt, 'acute') !== false ||
                      stripos($userPrompt, 'ugd') !== false ||
                      stripos($userPrompt, 'chest pain') !== false ||
                      stripos($userPrompt, 'dyspnea') !== false;
        
        $settingDescription = $isEmergency ? 
            "Busy emergency department (UGD) setting with medical equipment in background, IV poles, monitoring screens, medical staff partially visible, typical hospital emergency atmosphere" :
            "Clean clinical examination room with proper medical lighting, examination table, medical equipment neatly arranged";
        
        $enhancedPrompt = sprintf(
            "Create an animated hyperrealistic medical visualization showing: %s\n\n" .
            "PATIENT DETAILS:\n" .
            "- Age: %s years old\n" .
            "- Gender: %s\n" .
            "- Ethnicity: %s\n" .
            "- Expression: Show appropriate medical condition expression (pain, discomfort, concern, or distress based on condition)\n" .
            "- Position: Natural clinical positioning based on condition\n\n" .
            "CLINICAL SETTING:\n" .
            "- Location: %s\n" .
            "- Lighting: Clinical hospital lighting mix (overhead lights + natural light from windows)\n" .
            "- Atmosphere: Authentic hospital environment with subtle motion blur effects\n\n" .
            "VISUAL STYLE REQUIREMENTS:\n" .
            "- Animated hyperrealistic style with cinematic quality\n" .
            "- 8K resolution, photorealistic detail\n" .
            "- Natural skin texture with realistic pores and fine details\n" .
            "- Subtle animations: breathing movements, blinking, slight micro-expressions\n" .
            "- Depth of field with medical equipment in soft focus\n" .
            "- Color grading: Natural hospital colors with slight cinematic teal-orange contrast\n" .
            "- Medical accuracy: Proper positioning, equipment, and clinical protocols\n" .
            "- Educational value: Clear visualization of medical condition while maintaining patient dignity\n\n" .
            "TECHNICAL SPECIFICATIONS:\n" .
            "- Photorealistic rendering with subsurface scattering\n" .
            "Safe for medical education, non-graphic but clinically accurate",
            $userPrompt,
            $age,
            $gender,
            $ethnicity,
            $settingDescription
        );

        return $enhancedPrompt;
    }

    /**
     * Save generated image to storage
     */
    private function saveImage(string $base64Data, string $mimeType, string $originalPrompt, string $enhancedPrompt = null, array $options = []): array
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

        // Create database record
        $visualization = PatientVisualization::create([
            'original_prompt' => $originalPrompt,
            'enhanced_prompt' => $enhancedPrompt ?? $originalPrompt,
            'prompt_hash' => md5($originalPrompt),
            'image_path' => $filename,
            'image_url' => $disk->url($filename),
            'mime_type' => $mimeType,
            'generation_options' => $options,
            'generated_at' => now(),
        ]);

        Log::info('Database record created', [
            'visualization_id' => $visualization->id,
            'image_path' => $filename,
            'image_url' => $visualization->image_url
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
        $promptHash = md5($prompt);
        
        // Check if we have a recent cached visualization (within 24 hours)
        $cachedVisualization = PatientVisualization::where('prompt_hash', $promptHash)
            ->where('generated_at', '>', now()->subHours(24))
            ->whereNotNull('image_path')
            ->first();
            
        if ($cachedVisualization && $cachedVisualization->imageExists()) {
            Log::info('Using cached visualization', [
                'visualization_id' => $cachedVisualization->id,
                'prompt_hash' => $promptHash,
                'generated_at' => $cachedVisualization->generated_at
            ]);
            
            return [
                'success' => true,
                'image_url' => $cachedVisualization->image_url,
                'image_path' => $cachedVisualization->image_path,
                'prompt' => $prompt,
                'generated_at' => $cachedVisualization->generated_at,
                'demographics' => $options['demographics'] ?? null,
                'cached' => true,
                'type' => 'cached'
            ];
        }
        
        // Generate new visualization
        return $this->generateVisualization($prompt, $options);
    }

    /**
     * Get common medical scenario prompts for quick generation
     */
    public function getCommonPrompts(): array
    {
        return [
            'chest-pain' => [
                'prompt' => '45-year-old male patient experiencing acute substernal chest pain, radiating to left arm and jaw, diaphoretic, sitting upright on emergency department bed with IV line, cardiac monitor visible, showing expression of severe discomfort and anxiety',
                'description' => 'Acute Coronary Syndrome - Emergency Department',
                'category' => 'cardiology',
                'demographics' => ['age' => 45, 'gender' => 'male', 'ethnicity' => 'Southeast Asian']
            ],
            'dyspnea' => [
                'prompt' => '62-year-old female patient with acute respiratory distress, tripod position, using accessory muscles, nasal flaring, oxygen mask in place, emergency department setting with respiratory therapist attending',
                'description' => 'Acute Respiratory Failure - Emergency',
                'category' => 'pulmonology',
                'demographics' => ['age' => 62, 'gender' => 'female', 'ethnicity' => 'Southeast Asian']
            ],
            'abdominal-pain' => [
                'prompt' => '38-year-old patient with severe acute abdominal pain, lying in fetal position on emergency stretcher, guarding abdomen, facial expression of acute distress, emergency department with surgical team preparing',
                'description' => 'Acute Abdomen - Surgical Emergency',
                'category' => 'gastroenterology',
                'demographics' => ['age' => 38, 'gender' => 'patient', 'ethnicity' => 'Southeast Asian']
            ],
            'headache' => [
                'prompt' => '35-year-old patient with severe headache and photophobia, darkened examination room, patient lying supine with eyes closed, hand on forehead, neurological examination in progress',
                'description' => 'Acute Severe Headache - Neurological Assessment',
                'category' => 'neurology',
                'demographics' => ['age' => 35, 'gender' => 'patient', 'ethnicity' => 'Southeast Asian']
            ],
            'elderly-frail' => [
                'prompt' => '78-year-old frail elderly patient in ICU setting, multiple monitoring devices, IV lines, oxygen cannula, family member holding hand, showing signs of complex medical condition',
                'description' => 'Complex Geriatric Patient - Critical Care',
                'category' => 'geriatrics',
                'demographics' => ['age' => 78, 'gender' => 'patient', 'ethnicity' => 'Southeast Asian']
            ],
            'pediatric' => [
                'prompt' => '8-year-old child with high fever, sitting on pediatric examination table, parent comforting, pediatrician examining with otoscope, colorful pediatric emergency room setting',
                'description' => 'Pediatric Fever Assessment - Emergency',
                'category' => 'pediatrics',
                'demographics' => ['age' => 8, 'gender' => 'patient', 'ethnicity' => 'Southeast Asian']
            ]
        ];
    }
}