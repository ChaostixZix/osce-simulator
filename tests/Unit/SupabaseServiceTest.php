<?php

use App\Services\SupabaseService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;

test('supabase service can be instantiated', function () {
    $service = new SupabaseService();
    $this->assertInstanceOf(SupabaseService::class, $service);
});

test('sign up creates new user', function () {
    // Mock HTTP client
    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('post')
        ->with('/auth/v1/signup', Mockery::on(function ($options) {
            return $options['json']['email'] === 'test@example.com' &&
                   $options['json']['password'] === 'password123';
        }))
        ->andReturn(new Response(200, [], json_encode([
            'user' => ['id' => 'new-user-id'],
            'session' => ['access_token' => 'test-token']
        ])));

    // Create service with mocked client
    $service = new SupabaseService();
    $reflection = new ReflectionClass($service);
    $clientProperty = $reflection->getProperty('client');
    $clientProperty->setAccessible(true);
    $clientProperty->setValue($service, $mockClient);

    $result = $service->signUp([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $this->assertEquals('new-user-id', $result['user']['id']);
});

test('sign in with password returns user and session', function () {
    // Mock HTTP client
    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('post')
        ->with('/auth/v1/token?grant_type=password', Mockery::on(function ($options) {
            return $options['json']['email'] === 'test@example.com';
        }))
        ->andReturn(new Response(200, [], json_encode([
            'user' => ['id' => 'user-id', 'email' => 'test@example.com'],
            'session' => ['access_token' => 'access-token', 'refresh_token' => 'refresh-token']
        ])));

    $service = new SupabaseService();
    $reflection = new ReflectionClass($service);
    $clientProperty = $reflection->getProperty('client');
    $clientProperty->setAccessible(true);
    $clientProperty->setValue($service, $mockClient);

    $result = $service->signInWithPassword([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $this->assertEquals('user-id', $result['user']['id']);
    $this->assertEquals('access-token', $result['session']['access_token']);
});

test('verify token validates against supabase api', function () {
    // Mock HTTP client
    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('get')
        ->with('/auth/v1/user', Mockery::on(function ($options) {
            return $options['headers']['Authorization'] === 'Bearer valid-token';
        }))
        ->andReturn(new Response(200, [], json_encode([
            'id' => 'user-id',
            'email' => 'test@example.com',
            'aud' => 'authenticated'
        ])));

    $service = new SupabaseService();
    $reflection = new ReflectionClass($service);
    $clientProperty = $reflection->getProperty('client');
    $clientProperty->setAccessible(true);
    $clientProperty->setValue($service, $mockClient);

    $result = $service->verifyToken('valid-token');

    $this->assertEquals('user-id', $result['id']);
    $this->assertEquals('authenticated', $result['aud']);
});

test('verify token returns false for invalid token', function () {
    // Mock HTTP client to return 401
    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('get')
        ->andThrow(new \GuzzleHttp\Exception\ClientException('Unauthorized', new \GuzzleHttp\Psr7\Request('GET', 'test')));

    $service = new SupabaseService();
    $reflection = new ReflectionClass($service);
    $clientProperty = $reflection->getProperty('client');
    $clientProperty->setAccessible(true);
    $clientProperty->setValue($service, $mockClient);

    $result = $service->verifyToken('invalid-token');

    $this->assertFalse($result);
});

test('refresh token renews access token', function () {
    // Mock HTTP client
    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('post')
        ->with('/auth/v1/token?grant_type=refresh_token', Mockery::on(function ($options) {
            return $options['json']['refresh_token'] === 'refresh-token';
        }))
        ->andReturn(new Response(200, [], json_encode([
            'access_token' => 'new-access-token',
            'refresh_token' => 'new-refresh-token'
        ])));

    $service = new SupabaseService();
    $reflection = new ReflectionClass($service);
    $clientProperty = $reflection->getProperty('client');
    $clientProperty->setAccessible(true);
    $clientProperty->setValue($service, $mockClient);

    $result = $service->refreshToken('refresh-token');

    $this->assertEquals('new-access-token', $result['access_token']);
    $this->assertEquals('new-refresh-token', $result['refresh_token']);
});

test('admin create user uses service role key', function () {
    // Mock HTTP client to check for service role key
    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('post')
        ->with('/auth/v1/admin/users', Mockery::on(function ($options) {
            return $options['headers']['Authorization'] === 'Bearer service-role-key' &&
                   $options['json']['email'] === 'admin@example.com';
        }))
        ->andReturn(new Response(200, [], json_encode([
            'id' => 'new-admin-user',
            'email' => 'admin@example.com'
        ])));

    $service = new SupabaseService();
    
    // Set service role key
    $reflection = new ReflectionClass($service);
    $serviceRoleProperty = $reflection->getProperty('serviceRoleKey');
    $serviceRoleProperty->setAccessible(true);
    $serviceRoleProperty->setValue($service, 'service-role-key');
    
    // Replace client
    $clientProperty = $reflection->getProperty('client');
    $clientProperty->setAccessible(true);
    $clientProperty->setValue($service, $mockClient);

    $result = $service->createUser([
        'email' => 'admin@example.com',
        'password' => 'password123',
    ]);

    $this->assertEquals('new-admin-user', $result['id']);
});

test('generate magic link returns correct URL', function () {
    // Mock HTTP client
    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('post')
        ->andReturn(new Response(200, [], json_encode([
            'properties' => ['link' => 'https://example.com/magic-link']
        ])));

    $service = new SupabaseService();
    $reflection = new ReflectionClass($service);
    $clientProperty = $reflection->getProperty('client');
    $clientProperty->setAccessible(true);
    $clientProperty->setValue($service, $mockClient);

    $result = $service->generateMagicLink('test@example.com', 'https://app.com/callback');

    $this->assertArrayHasKey('properties', $result);
});