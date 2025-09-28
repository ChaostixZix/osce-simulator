<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class NanoBananaService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    
    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }
    
    /**
     * Generate an image using Gemini API
     */
    public function generateImage(string $prompt, array $options = []): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ])->post("{$this->baseUrl}/models/gemini-2.5-flash-image-preview:generateContent", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => array_merge([
                    'temperature' => 0.7,
                    'topK' => 1,
                    'topP' => 1,
                    'maxOutputTokens' => 2048,
                    'responseModalities' => ['TEXT', 'IMAGE']
                ], $options),
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_NONE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_NONE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_NONE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_NONE'
                    ]
                ]
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Extract generated content
                if (isset($data['candidates'][0]['content']['parts'])) {
                    $parts = $data['candidates'][0]['content']['parts'];
                    $result = [
                        'success' => true,
                        'text' => '',
                        'images' => []
                    ];
                    
                    foreach ($parts as $part) {
                        if (isset($part['text'])) {
                            $result['text'] .= $part['text'];
                        } elseif (isset($part['inline_data'])) {
                            // Handle inline image data
                            $mimeType = $part['inline_data']['mime_type'];
                            $base64Data = $part['inline_data']['data'];
                            
                            // Generate unique filename
                            $filename = 'generated-' . Str::random(10) . '.' . $this->getExtensionFromMime($mimeType);
                            $path = "public/generated-images/{$filename}";
                            
                            // Save the image
                            Storage::put($path, base64_decode($base64Data));
                            
                            $result['images'][] = [
                                'url' => Storage::url($path),
                                'path' => $path,
                                'mime_type' => $mimeType,
                                'size' => strlen(base64_decode($base64Data))
                            ];
                        }
                    }
                    
                    return $result;
                }
            }
            
            return [
                'success' => false,
                'error' => $response->body()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate SVG logo for osce.simulator
     */
    public function generateOsceLogo(): array
    {
        $prompt = <<<'PROMPT'
Generate a professional SVG logo for "osce.simulator" - an OSCE (Objective Structured Clinical Examination) training platform for medical students.

Design requirements:
- A stethoscope forming a heart shape as the main icon
- Modern, clean medical aesthetic
- Blue (#2563eb) and white color scheme with gray accents
- "osce.simulator" text in a professional, modern sans-serif font
- SVG format with clean, scalable paths
- Should look professional and trustworthy for medical education
- Include subtle gradient effects for depth
- Dimensions: approximately 200x60px for the full logo

Please respond with ONLY the complete SVG code wrapped in ```svg and ``` tags. No additional text or explanation.
PROMPT;

        return $this->generateImage($prompt);
    }
    
    /**
     * Get file extension from MIME type
     */
    protected function getExtensionFromMime(string $mimeType): string
    {
        $extensions = [
            'image/svg+xml' => 'svg',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp'
        ];
        
        return $extensions[$mimeType] ?? 'png';
    }
    
    /**
     * Create favicon files from an image
     */
    public function createFavicons(string $sourcePath): array
    {
        $favicons = [
            'favicon.ico' => [32, 32],
            'favicon-16x16.png' => [16, 16],
            'favicon-32x32.png' => [32, 32],
            'apple-touch-icon.png' => [180, 180],
            'android-chrome-192x192.png' => [192, 192],
            'android-chrome-512x512.png' => [512, 512],
        ];
        
        $created = [];
        
        foreach ($favicons as $filename => $size) {
            $targetPath = public_path($filename);
            
            try {
                // Load the source image
                $img = Image::make($sourcePath);
                
                // Resize the image
                $img->resize($size[0], $size[1], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                
                // Save the image
                if ($filename === 'favicon.ico') {
                    // For ICO, we'll save as PNG and convert later if needed
                    $img->save(str_replace('.ico', '.png', $targetPath));
                    $created[$filename] = str_replace('.ico', '.png', $targetPath);
                } else {
                    $img->save($targetPath);
                    $created[$filename] = $targetPath;
                }
                
            } catch (\Exception $e) {
                \Log::error("Failed to create favicon {$filename}: " . $e->getMessage());
            }
        }
        
        return $created;
    }
}