<?php

namespace App\Http\Controllers;

use App\Services\NanoBananaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LogoGeneratorController extends Controller
{
    protected $nanoBananaService;
    
    public function __construct(NanoBananaService $nanoBananaService)
    {
        $this->nanoBananaService = $nanoBananaService;
    }
    
    public function generate(Request $request)
    {
        // Generate logo using Nano-Banana service
        $result = $this->nanoBananaService->generateOsceLogo();
        
        if ($result['success']) {
            // Extract SVG from text response
            $svgContent = $this->extractSvgFromText($result['text']);
            
            if ($svgContent) {
                // Save the generated SVG logo
                $logoPath = public_path('logo-generated.svg');
                file_put_contents($logoPath, $svgContent);
                
                // Update the main logo file
                file_put_contents(public_path('logo.svg'), $svgContent);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Logo generated successfully using Nano-Banana AI',
                    'svg' => $svgContent,
                    'images' => $result['images'] ?? []
                ]);
            }
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to generate logo',
            'error' => $result['error'] ?? 'Unknown error'
        ], 500);
    }
    
    /**
     * Extract SVG content from AI response text
     */
    protected function extractSvgFromText(string $text): ?string
    {
        // Look for SVG tags in the text
        if (preg_match('/<svg[^>]*>.*?<\/svg>/s', $text, $matches)) {
            return $matches[0];
        }
        
        // If no SVG found, return null
        return null;
    }
    
    public function download()
    {
        $path = public_path('logo-generated.svg');
        
        if (file_exists($path)) {
            return response()->download($path, 'osce-simulator-logo.svg');
        }
        
        return response()->json([
            'success' => false,
            'message' => 'No generated logo found'
        ], 404);
    }
    
    public function createFavicons()
    {
        $sourcePath = public_path('logo.svg');
        
        if (!file_exists($sourcePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Source logo not found'
            ], 404);
        }
        
        // Create different favicon sizes
        $favicons = $this->nanoBananaService->createFavicons($sourcePath);
        
        return response()->json([
            'success' => true,
            'message' => 'Favicons created successfully',
            'favicons' => $favicons
        ]);
    }
}