<?php

use App\Services\AppwriteService;
use RuntimeException;

beforeEach(function () {
    // Reset config before each test
    config()->set('appwrite', [
        'enabled' => false,
        'endpoint' => 'https://cloud.appwrite.io/v1',
        'project_id' => 'test-project',
        'api_key' => 'test-api-key',
        'database_id' => 'test-db',
        'migrations_collection_id' => 'migrations',
        'permissions' => [
            'read' => ['role:all'],
            'create' => ['role:all'],
            'update' => ['role:all'],
            'delete' => ['role:all'],
        ],
    ]);
});

test('service reports disabled when appwrite is disabled', function () {
    $service = new AppwriteService();
    
    expect($service->isEnabled())->toBeFalse();
});

test('service reports enabled when appwrite is enabled', function () {
    config()->set('appwrite.enabled', true);
    
    $service = new AppwriteService();
    
    expect($service->isEnabled())->toBeTrue();
});

test('service throws exception when trying to operate while disabled', function () {
    $service = new AppwriteService();
    
    $service->testConnectivity();
})->throws(RuntimeException::class, 'Appwrite integration is disabled');

test('service validates configuration completeness', function () {
    config()->set('appwrite.enabled', true);
    config()->set('appwrite.endpoint', '');
    
    $service = new AppwriteService();
    
    $service->testConnectivity();
})->throws(RuntimeException::class, 'Appwrite configuration is incomplete');

test('service validates endpoint url format', function () {
    config()->set('appwrite.enabled', true);
    config()->set('appwrite.endpoint', 'not-a-url');
    
    $service = new AppwriteService();
    
    $service->testConnectivity();
})->throws(RuntimeException::class, 'Invalid Appwrite endpoint URL');

test('service validates project id format', function () {
    config()->set('appwrite.enabled', true);
    config()->set('appwrite.project_id', 'invalid project id!@#');
    
    $service = new AppwriteService();
    
    $service->testConnectivity();
})->throws(RuntimeException::class, 'Invalid Appwrite project ID format');

test('service rejects placeholder api keys', function () {
    config()->set('appwrite.enabled', true);
    config()->set('appwrite.api_key', 'your_appwrite_api_key');
    
    $service = new AppwriteService();
    
    $service->testConnectivity();
})->throws(RuntimeException::class, 'API key appears to be a placeholder');

test('service validates permission roles', function () {
    config()->set('appwrite', [
        'enabled' => true,
        'endpoint' => 'https://cloud.appwrite.io/v1',
        'project_id' => 'test-project',
        'api_key' => 'test-api-key',
        'database_id' => 'test-db',
        'migrations_collection_id' => 'migrations',
        'permissions' => [
            'read' => ['invalid-role-format'],
            'create' => ['role:all'],
            'update' => ['role:all'],
            'delete' => ['role:all'],
        ],
    ]);
    
    $service = new AppwriteService();
    
    $service->testConnectivity();
})->throws(RuntimeException::class, 'Invalid permission role format');

test('service accepts valid permission role formats', function () {
    $validRoles = [
        'role:all',
        'role:member',
        'role:guest',
        'users',
        'user:123',
        'team:abc',
        'label:test',
    ];
    
    foreach ($validRoles as $role) {
        config()->set('appwrite', [
            'enabled' => true,
            'endpoint' => 'https://cloud.appwrite.io/v1',
            'project_id' => 'test-project',
            'api_key' => 'test-api-key',
            'database_id' => 'test-db',
            'migrations_collection_id' => 'migrations',
            'permissions' => [
                'read' => [$role],
                'create' => ['role:all'],
                'update' => ['role:all'],
                'delete' => ['role:all'],
            ],
        ]);
        
        $service = new AppwriteService();
        
        // This should not throw an exception during validation
        // We expect it to fail at the network level, not validation
        try {
            $service->testConnectivity();
        } catch (RuntimeException $e) {
            // Should not be a validation error
            expect($e->getMessage())->not->toContain('Invalid permission role format');
        }
    }
    
    expect(true)->toBeTrue(); // Test passed if we got here
});