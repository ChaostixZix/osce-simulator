<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class OptimizeQueuePerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:optimize-performance';

    /**
     * The console command description.
     */
    protected $description = 'Apply performance optimizations for queue system';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Applying queue performance optimizations...');

        // 1. Clear all caches
        $this->info('Clearing caches...');
        Cache::flush();
        
        // 2. Optimize Redis connection
        $this->info('Optimizing Redis configuration...');
        Config::set('database.redis.default.options.serializer', 'php');
        Config::set('database.redis.default.options.compression', 'lz4');
        
        // 3. Set queue-specific optimizations
        $this->info('Setting queue optimizations...');
        Config::set('queue.connections.redis.retry_after', 60);
        Config::set('queue.connections.redis.block_for', 0);
        
        // 4. Warm up cache for queue checks
        $this->info('Warming up cache...');
        Cache::put('has_queued_assessments', false, 60);
        Cache::put('assessment_avg_processing_time', 180, 300);
        
        $this->info('✅ Queue performance optimizations applied!');
        $this->line('');
        $this->line('Recommended next steps:');
        $this->line('1. Restart queue workers: php artisan queue:restart');
        $this->line('2. Start optimized worker: php artisan queue:work redis --queue=assessments,management,default --tries=3 --timeout=300');
        $this->line('3. Monitor performance: php artisan queue:monitor');
    }
}