<?php

namespace App\Console\Commands;

use App\Services\NanoBananaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateLogoCommand extends Command
{
    protected $signature = 'logo:generate {--save : Save the generated logo}';
    protected $description = 'Generate a logo using Nano-Banana AI';

    protected $nanoBananaService;

    public function __construct(NanoBananaService $nanoBananaService)
    {
        parent::__construct();
        $this->nanoBananaService = $nanoBananaService;
    }

    public function handle()
    {
        $this->info('Generating logo for osce.simulator using Nano-Banana AI...');
        
        $result = $this->nanoBananaService->generateOsceLogo();
        
        if ($result['success']) {
            $this->info('Logo generation successful!');
            
            // Extract SVG content
            $svgContent = $this->extractSvgFromText($result['text']);
            
            if ($svgContent) {
                if ($this->option('save')) {
                    // Save to public directory
                    File::put(public_path('logo.svg'), $svgContent);
                    File::put(public_path('logo-generated.svg'), $svgContent);
                    
                    $this->info('Logo saved to:');
                    $this->line('- ' . public_path('logo.svg'));
                    $this->line('- ' . public_path('logo-generated.svg'));
                    
                    // Also save the response text for reference
                    File::put(storage_path('logs/logo-generation-response.txt'), $result['text']);
                    $this->info('Generation response saved to storage/logs/logo-generation-response.txt');
                } else {
                    $this->info('Generated SVG content:');
                    $this->line($svgContent);
                }
                
                // Show any generated images
                if (!empty($result['images'])) {
                    $this->info('Additional images generated:');
                    foreach ($result['images'] as $image) {
                        $this->line("- {$image['url']} ({$image['mime_type']})");
                    }
                }
            } else {
                $this->error('No SVG content found in the response');
                $this->line('Raw response:');
                $this->line($result['text']);
            }
        } else {
            $this->error('Failed to generate logo');
            $this->error('Error: ' . ($result['error'] ?? 'Unknown error'));
        }
        
        return $result['success'] ? 0 : 1;
    }
    
    protected function extractSvgFromText(string $text): ?string
    {
        if (preg_match('/<svg[^>]*>.*?<\/svg>/s', $text, $matches)) {
            return $matches[0];
        }
        
        return null;
    }
}