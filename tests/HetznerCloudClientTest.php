<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PoorPlebs\HetznerCloudSdk\GuzzleHttp\Exception\ClientException;
use PoorPlebs\HetznerCloudSdk\GuzzleHttp\Exception\ServerException;
use PoorPlebs\HetznerCloudSdk\HetznerCloudClient;
use PoorPlebs\HetznerCloudSdk\Obfuscator\HetznerApiTokenObfuscator;
use PoorPlebs\HetznerCloudSdk\Psr\Log\WrappedLogger;
use PoorPlebs\HetznerCloudSdk\Resources\ActionsResource;
use PoorPlebs\HetznerCloudSdk\Resources\FirewallsResource;
use PoorPlebs\HetznerCloudSdk\Resources\ServersResource;
use PoorPlebs\HetznerCloudSdk\Resources\SshKeysResource;
use PoorPlebs\HetznerCloudSdk\Tests\Support\InMemoryCache;
use Psr\Log\NullLogger;

covers(
    HetznerCloudClient::class,
    HetznerApiTokenObfuscator::class,
    WrappedLogger::class,
);

/**
 * @param array<int,Response|Throwable> $responses
 * @param array<int,array<string,mixed>> $history
 */
function makeClientWithHistory(array $responses, array &$history): HetznerCloudClient
{
    $mockHandler = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::history($history));

    return new HetznerCloudClient(
        apiToken: 'test-api-token-secret',
        cache: new InMemoryCache(),
        config: [
            'handler' => $handlerStack,
        ],
    );
}

/**
 * @param array<string,mixed> $payload
 */
function hetznerResponse(array $payload, int $status = 200): Response
{
    return new Response(
        $status,
        ['Content-Type' => 'application/json'],
        json_encode($payload, JSON_THROW_ON_ERROR),
    );
}

/**
 * @param array<int,Response|Throwable> $queue
 * @param array<int,array<string,mixed>> $history
 */
function makeClientWithHttpErrorsMiddleware(array $queue, array &$history): HetznerCloudClient
{
    $mockHandler = new MockHandler($queue);
    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::history($history));

    $reflection = new ReflectionClass(HetznerCloudClient::class);
    $httpErrorsMethod = $reflection->getMethod('httpErrors');
    $httpErrorsMethod->setAccessible(true);
    /** @var callable $httpErrors */
    $httpErrors = $httpErrorsMethod->invoke(null);
    $handlerStack->remove('http_errors');
    $handlerStack->unshift($httpErrors, 'http_errors');

    return new HetznerCloudClient(
        apiToken: 'test-api-token-secret',
        cache: new InMemoryCache(),
        config: [
            'handler' => $handlerStack,
        ],
    );
}

it('returns resource accessor instances', function (): void {
    $client = new HetznerCloudClient(
        apiToken: 'test-api-token-secret',
        cache: new InMemoryCache(),
    );

    expect($client->servers())->toBeInstanceOf(ServersResource::class)
        ->and($client->firewalls())->toBeInstanceOf(FirewallsResource::class)
        ->and($client->sshKeys())->toBeInstanceOf(SshKeysResource::class)
        ->and($client->actions())->toBeInstanceOf(ActionsResource::class);
});

it('returns the same resource instance on repeated calls', function (): void {
    $client = new HetznerCloudClient(
        apiToken: 'test-api-token-secret',
        cache: new InMemoryCache(),
    );

    expect($client->servers())->toBe($client->servers())
        ->and($client->firewalls())->toBe($client->firewalls())
        ->and($client->sshKeys())->toBe($client->sshKeys())
        ->and($client->actions())->toBe($client->actions());
});

it('allows setting a logger', function (): void {
    $client = new HetznerCloudClient(
        apiToken: 'test-api-token-secret',
        cache: new InMemoryCache(),
    );

    $client->setLogger(new NullLogger());

    expect($client)->toBeInstanceOf(HetznerCloudClient::class);
});

it('sends authorization header with bearer token', function (): void {
    $history = [];

    $client = makeClientWithHistory([
        hetznerResponse(['servers' => []]),
    ], $history);

    $client->servers()->list()->wait();

    expect($history[0]['request']->getHeaderLine('Authorization'))
        ->toBe('Bearer test-api-token-secret');
});

it('converts 5xx responses into server exceptions via http error middleware', function (): void {
    $history = [];

    $client = makeClientWithHttpErrorsMiddleware([
        new Response(
            500,
            ['Content-Type' => 'application/json'],
            '{"error":{"message":"Internal server error","code":"server_error"}}',
        ),
    ], $history);

    try {
        $client->servers()->list()->wait();

        expect()->fail('Expected a server exception.');
    } catch (ServerException $exception) {
        expect($exception->getMessage())
            ->toContain('Server error')
            ->not->toContain('test-api-token-secret');
    }
});

it('converts 4xx responses into client exceptions via http error middleware', function (): void {
    $history = [];

    $client = makeClientWithHttpErrorsMiddleware([
        new Response(
            403,
            ['Content-Type' => 'application/json'],
            '{"error":{"message":"Forbidden","code":"forbidden"}}',
        ),
    ], $history);

    try {
        $client->servers()->list()->wait();

        expect()->fail('Expected a client exception.');
    } catch (ClientException $exception) {
        expect($exception->getMessage())
            ->toContain('Client error')
            ->not->toContain('test-api-token-secret');
    }
});

it('keeps successful responses untouched in custom http error middleware', function (): void {
    $history = [];

    $client = makeClientWithHttpErrorsMiddleware([
        hetznerResponse(['servers' => []]),
    ], $history);

    $response = $client->servers()->list()->wait();

    expect($response->result)->toBeArray()->toBeEmpty();
});

it('bypasses http error conversion when request options disable http_errors', function (): void {
    $history = [];

    $client = makeClientWithHttpErrorsMiddleware([
        hetznerResponse(['servers' => []]),
    ], $history);

    $response = $client->servers()->list()->wait();

    expect($response->result)->toBeArray();
});

it('obfuscates token in connect exception messages', function (): void {
    $history = [];

    $client = makeClientWithHttpErrorsMiddleware([
        new ConnectException(
            'Connection error with Authorization: Bearer test-api-token-secret',
            new Request('GET', 'https://api.hetzner.cloud/v1/servers'),
        ),
    ], $history);

    try {
        $client->servers()->list()->wait();

        expect()->fail('Expected a connect exception.');
    } catch (ConnectException $exception) {
        expect($exception->getMessage())
            ->toContain('Bearer **********')
            ->not->toContain('test-api-token-secret');
    }
});

it('rethrows non-connect exceptions from the http error middleware', function (): void {
    $history = [];

    $client = makeClientWithHttpErrorsMiddleware([
        new RuntimeException('unexpected transport failure'),
    ], $history);

    expect(fn (): mixed => $client->servers()->list()->wait())
        ->toThrow(RuntimeException::class, 'unexpected transport failure');
});
