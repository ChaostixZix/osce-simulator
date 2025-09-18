<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UniversalAIService;
use App\Services\GeminiService;
use App\Services\OpenAIAzureService;
use Illuminate\Support\Facades\Log;

class TestAIProviders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test {--provider=all : Test specific provider (gemini, openai, or all)} {--detailed : Show detailed response metadata}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AI providers connection and verify distinct responses with comprehensive validation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $provider = $this->option('provider');
        $verbose = $this->option('detailed');

        $this->info('🧪 Enhanced AI Provider Testing - Verifying Real AI Responses');
        $this->info('================================================================');
        $this->newLine();

        $results = [];

        if ($provider === 'all' || $provider === 'openai') {
            $results['openai'] = $this->testProviderWithDistinctScenario('openai', $verbose);
        }

        if ($provider === 'all' || $provider === 'gemini') {
            $results['gemini'] = $this->testProviderWithDistinctScenario('gemini', $verbose);
        }

        if ($provider === 'all') {
            $this->compareResults($results);
        }

        $this->newLine();
        $this->info('✅ Enhanced AI Provider testing completed!');
    }

    private function testProviderWithDistinctScenario(string $provider, bool $verbose): array
    {
        $timestamp = date('Y-m-d H:i:s');
        $testId = uniqid('test_');

        $this->info("🔍 Testing {$provider} with unique scenario (ID: {$testId})");
        $this->line("   Timestamp: {$timestamp}");

        try {
            // Create UniversalAIService with explicit provider
            $aiService = new UniversalAIService($provider);

            // Test connection first
            $connection = $aiService->testConnection();
            if (!$connection['success']) {
                $this->error("❌ Connection failed: {$connection['error']}");
                return ['success' => false, 'error' => 'Connection failed'];
            }

            $this->line("✅ Connection: SUCCESS");
            $this->line("   Provider: {$connection['provider']}");
            $this->line("   Model: {$connection['model']}");

            // Create distinct test scenarios for each provider
            $scenario = $this->getProviderSpecificScenario($provider, $testId, $timestamp);

            $this->line("🩺 Testing doctor-patient conversation...");
            $this->line("   Scenario: {$scenario['description']}");

            // Execute the AI call with scenario
            $response = $aiService->generateChatResponse(
                $scenario['systemPrompt'],
                $scenario['messages'],
                $scenario['options']
            );

            // Analyze response
            $analysis = $this->analyzeResponse($response, $provider, $testId);

            // Display results
            $this->displayResults($response, $analysis, $verbose);

            return [
                'success' => true,
                'provider' => $provider,
                'scenario' => $scenario['description'],
                'response' => $response,
                'analysis' => $analysis,
                'test_id' => $testId
            ];

        } catch (\Exception $e) {
            $this->error("❌ {$provider} test failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'provider' => $provider];
        }
    }

    private function getProviderSpecificScenario(string $provider, string $testId, string $timestamp): array
    {
        if ($provider === 'openai') {
            return [
                'description' => 'Businessman with acute chest pain at office',
                'systemPrompt' => "You are a 45-year-old businessman named Ahmad who is experiencing acute chest pain while working late at the office. You are stressed about an important presentation tomorrow. You speak Indonesian primarily but can understand English. You are worried this might be a heart attack. Include this unique identifier in your response naturally: TEST-{$testId}. Current time: {$timestamp}",
                'messages' => [
                    ['sender' => 'user', 'message' => 'Good evening, I am Dr. Sarah. I understand you called for help. Can you tell me what\'s happening?']
                ],
                'options' => [
                    'temperature' => 0.8, // Slightly higher for more variation
                    'max_tokens' => 150
                ]
            ];
        } else {
            return [
                'description' => 'Teacher with shortness of breath after class',
                'systemPrompt' => "You are a 32-year-old female teacher named Sari who just finished teaching and suddenly feels short of breath. You are concerned about your students' safety if something happens to you. You speak Indonesian fluently. You think it might be related to stress from recent school evaluations. Include this unique identifier in your response naturally: GEMINI-{$testId}. Current time: {$timestamp}",
                'messages' => [
                    ['sender' => 'user', 'message' => 'Selamat sore, saya Dr. Budi. Saya mendengar ibu mengalami kesulitan bernapas. Bisa ceritakan apa yang ibu rasakan?']
                ],
                'options' => [
                    'temperature' => 0.9, // Higher temperature for Gemini
                    'max_tokens' => 150
                ]
            ];
        }
    }

    private function analyzeResponse(array $response, string $expectedProvider, string $testId): array
    {
        $analysis = [
            'is_real_ai' => false,
            'provider_matches' => false,
            'contains_test_id' => false,
            'response_length' => strlen($response['content']),
            'response_time' => $response['metadata']['response_time'],
            'is_fallback' => $response['metadata']['is_fallback'],
            'warnings' => []
        ];

        // Check if response contains test ID (proves AI processed the prompt)
        $searchPattern = $expectedProvider === 'openai' ? "TEST-{$testId}" : "GEMINI-{$testId}";
        $analysis['contains_test_id'] = stripos($response['content'], $testId) !== false;

        // Check provider matches expectation
        $analysis['provider_matches'] = $response['metadata']['provider'] === $expectedProvider;

        // Check if it's a real AI response (not fallback)
        $analysis['is_real_ai'] = !$response['metadata']['is_fallback'] &&
                                  $response['metadata']['response_time'] > 0.1 && // Took some time to process
                                  $analysis['response_length'] > 20; // Has substantial content

        // Add warnings for suspicious responses
        if ($analysis['is_fallback']) {
            $analysis['warnings'][] = 'Response is marked as fallback - may not be from AI';
        }

        if (!$analysis['contains_test_id']) {
            $analysis['warnings'][] = 'Response does not contain test ID - AI may not have processed full prompt';
        }

        if ($analysis['response_time'] < 0.1) {
            $analysis['warnings'][] = 'Response time too fast - may be cached or fallback';
        }

        if (stripos($response['content'], 'apologize') !== false && stripos($response['content'], 'trouble') !== false) {
            $analysis['warnings'][] = 'Response contains fallback-like phrases';
        }

        return $analysis;
    }

    private function displayResults(array $response, array $analysis, bool $verbose): void
    {
        $content = $response['content'];
        $metadata = $response['metadata'];

        $this->line("   Patient Response: " . $content);
        $this->newLine();

        // Status indicators
        $realAiStatus = $analysis['is_real_ai'] ? '✅ REAL AI' : '❌ FALLBACK/FAKE';
        $providerStatus = $analysis['provider_matches'] ? '✅ CORRECT PROVIDER' : '❌ WRONG PROVIDER';
        $testIdStatus = $analysis['contains_test_id'] ? '✅ PROCESSED PROMPT' : '❌ GENERIC RESPONSE';

        $this->line("📊 Response Analysis:");
        $this->line("   Real AI Response: {$realAiStatus}");
        $this->line("   Provider Match: {$providerStatus}");
        $this->line("   Test ID Found: {$testIdStatus}");
        $this->line("   Response Time: {$metadata['response_time']}s");
        $this->line("   Content Length: {$analysis['response_length']} chars");

        if (!empty($analysis['warnings'])) {
            $this->newLine();
            $this->warn("⚠️  Warnings:");
            foreach ($analysis['warnings'] as $warning) {
                $this->line("   • {$warning}");
            }
        }

        if ($verbose) {
            $this->newLine();
            $this->line("🔧 Detailed Metadata:");
            $this->line("   Provider: {$metadata['provider']}");
            $this->line("   Model: {$metadata['model']}");
            $this->line("   Request ID: {$metadata['request_id']}");
            $this->line("   Is Fallback: " . ($metadata['is_fallback'] ? 'Yes' : 'No'));

            if (isset($metadata['usage'])) {
                $this->line("   Token Usage: {$metadata['usage']['total_tokens']} total");
            }
        }

        $this->newLine();
    }

    private function compareResults(array $results): void
    {
        $this->info('🔄 Comparing Provider Results');
        $this->line('================================');

        if (!isset($results['openai']) || !isset($results['gemini'])) {
            $this->warn('Cannot compare - not all providers were tested');
            return;
        }

        $openaiResult = $results['openai'];
        $geminiResult = $results['gemini'];

        if (!$openaiResult['success'] || !$geminiResult['success']) {
            $this->warn('Cannot compare - one or more tests failed');
            return;
        }

        $openaiContent = $openaiResult['response']['content'];
        $geminiContent = $geminiResult['response']['content'];

        // Calculate similarity (simple approach)
        $similarity = $this->calculateSimilarity($openaiContent, $geminiContent);

        $this->line("Response Similarity: {$similarity}%");

        if ($similarity > 80) {
            $this->warn("⚠️  Responses are {$similarity}% similar - may indicate fallback responses or identical training");
        } else {
            $this->line("✅ Responses are sufficiently different ({$similarity}% similarity)");
        }

        $this->line("\nOpenAI Response: " . substr($openaiContent, 0, 100) . '...');
        $this->line("Gemini Response: " . substr($geminiContent, 0, 100) . '...');

        // Compare metadata
        $this->line("\nResponse Times:");
        $this->line("   OpenAI: {$openaiResult['response']['metadata']['response_time']}s");
        $this->line("   Gemini: {$geminiResult['response']['metadata']['response_time']}s");
    }

    private function calculateSimilarity(string $text1, string $text2): int
    {
        $text1 = strtolower(trim($text1));
        $text2 = strtolower(trim($text2));

        if ($text1 === $text2) {
            return 100;
        }

        // Simple word-based similarity
        $words1 = explode(' ', $text1);
        $words2 = explode(' ', $text2);

        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));

        return round((count($intersection) / count($union)) * 100);
    }
}