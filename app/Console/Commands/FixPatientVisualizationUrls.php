<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PatientVisualization;

class FixPatientVisualizationUrls extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'patient-visualizer:fix-urls';

    /**
     * The console command description.
     */
    protected $description = 'Fix malformed image URLs in patient visualizations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing patient visualization URLs...');

        $visualizations = PatientVisualization::whereNotNull('image_url')
            ->where('image_url', 'LIKE', 'http:/%')
            ->get();

        $count = $visualizations->count();

        if ($count === 0) {
            $this->info('No malformed URLs found.');
            return 0;
        }

        $this->info("Found {$count} malformed URLs to fix.");

        foreach ($visualizations as $visualization) {
            // Clear the malformed URL so the accessor will regenerate it
            $visualization->image_url = null;
            $visualization->save();
            
            $this->line("Fixed visualization ID: {$visualization->id}");
        }

        $this->info('All malformed URLs have been fixed.');
        return 0;
    }
}