<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PoorPlebs\HetznerCloudSdk\HetznerCloudClient;
use PoorPlebs\HetznerCloudSdk\Models\Action;
use PoorPlebs\HetznerCloudSdk\Models\ActionError;
use PoorPlebs\HetznerCloudSdk\Resources\ActionsResource;
use PoorPlebs\HetznerCloudSdk\Responses\ActionResponse;
use PoorPlebs\HetznerCloudSdk\Tests\Support\InMemoryCache;

covers(
    ActionsResource::class,
    ActionResponse::class,
    Action::class,
    ActionError::class,
);

/**
 * @param array<int,Response> $responses
 * @param array<int,array<string,mixed>> $history
 */
function makeActionsClient(array $responses, array &$history): HetznerCloudClient
{
    $mockHandler = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::history($history));

    return new HetznerCloudClient(
        apiToken: 'test-token',
        cache: new InMemoryCache(),
        config: ['handler' => $handlerStack],
    );
}

it('gets a single action by id', function (): void {
    $history = [];

    $client = makeActionsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => [
                'id' => 42,
                'command' => 'start_server',
                'status' => 'success',
                'progress' => 100,
                'started' => '2024-01-01T00:00:00+00:00',
                'finished' => '2024-01-01T00:00:05+00:00',
                'error' => ['code' => '', 'message' => ''],
            ],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->actions()->get(42)->wait();

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result)->toBeInstanceOf(Action::class)
        ->and($response->result->id)->toBe(42)
        ->and($response->result->command)->toBe('start_server')
        ->and($response->result->status)->toBe('success')
        ->and($response->result->progress)->toBe(100)
        ->and($response->result->finished)->toBe('2024-01-01T00:00:05+00:00')
        ->and((string)$history[0]['request']->getUri())->toContain('/actions/42');
});

it('gets an action with error details', function (): void {
    $history = [];

    $client = makeActionsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => [
                'id' => 43,
                'command' => 'start_server',
                'status' => 'error',
                'progress' => 100,
                'started' => '2024-01-01T00:00:00+00:00',
                'finished' => '2024-01-01T00:00:05+00:00',
                'error' => ['code' => 'action_failed', 'message' => 'Server start failed'],
            ],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->actions()->get(43)->wait();

    expect($response->result->error)->not->toBeNull()
        ->and($response->result->error->code)->toBe('action_failed')
        ->and($response->result->error->message)->toBe('Server start failed');
});

it('polls an action that succeeds immediately', function (): void {
    $history = [];

    $client = makeActionsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => [
                'id' => 42, 'command' => 'start_server', 'status' => 'success',
                'progress' => 100, 'started' => '2024-01-01T00:00:00+00:00',
                'finished' => '2024-01-01T00:00:05+00:00', 'error' => ['code' => '', 'message' => ''],
            ],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->actions()->poll(42, sleep: static fn (float $s): null => null)->wait();

    expect($result)->toBeInstanceOf(Action::class)
        ->and($result->id)->toBe(42)
        ->and($result->status)->toBe('success')
        ->and($history)->toHaveCount(1);
});

it('polls an action that succeeds after retries', function (): void {
    $history = [];

    $client = makeActionsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => [
                'id' => 42, 'command' => 'start_server', 'status' => 'running',
                'progress' => 0, 'started' => '2024-01-01T00:00:00+00:00',
                'finished' => null, 'error' => ['code' => '', 'message' => ''],
            ],
        ], JSON_THROW_ON_ERROR)),
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => [
                'id' => 42, 'command' => 'start_server', 'status' => 'running',
                'progress' => 50, 'started' => '2024-01-01T00:00:00+00:00',
                'finished' => null, 'error' => ['code' => '', 'message' => ''],
            ],
        ], JSON_THROW_ON_ERROR)),
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => [
                'id' => 42, 'command' => 'start_server', 'status' => 'success',
                'progress' => 100, 'started' => '2024-01-01T00:00:00+00:00',
                'finished' => '2024-01-01T00:00:05+00:00', 'error' => ['code' => '', 'message' => ''],
            ],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->actions()->poll(42, sleep: static fn (float $s): null => null)->wait();

    expect($result)->toBeInstanceOf(Action::class)
        ->and($result->status)->toBe('success')
        ->and($history)->toHaveCount(3);
});

it('polls an action that ends with error status', function (): void {
    $history = [];

    $client = makeActionsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => [
                'id' => 42, 'command' => 'start_server', 'status' => 'error',
                'progress' => 100, 'started' => '2024-01-01T00:00:00+00:00',
                'finished' => '2024-01-01T00:00:05+00:00',
                'error' => ['code' => 'action_failed', 'message' => 'Server start failed'],
            ],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->actions()->poll(42, sleep: static fn (float $s): null => null)->wait();

    expect($result)->toBeInstanceOf(Action::class)
        ->and($result->status)->toBe('error')
        ->and($result->error)->not->toBeNull()
        ->and($result->error->code)->toBe('action_failed')
        ->and($history)->toHaveCount(1);
});

it('polls an action that exceeds max attempts and throws', function (): void {
    $history = [];

    $client = makeActionsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => [
                'id' => 42, 'command' => 'start_server', 'status' => 'running',
                'progress' => 0, 'started' => '2024-01-01T00:00:00+00:00',
                'finished' => null, 'error' => ['code' => '', 'message' => ''],
            ],
        ], JSON_THROW_ON_ERROR)),
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => [
                'id' => 42, 'command' => 'start_server', 'status' => 'running',
                'progress' => 50, 'started' => '2024-01-01T00:00:00+00:00',
                'finished' => null, 'error' => ['code' => '', 'message' => ''],
            ],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    expect(fn () => $client->actions()->poll(42, maxAttempts: 2, sleep: static fn (float $s): null => null)->wait())
        ->toThrow(RuntimeException::class, 'Action 42 did not complete within 2 attempts');
});

it('polls an action that exceeds max attempts with boundary of one', function (): void {
    $history = [];

    $client = makeActionsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => [
                'id' => 42, 'command' => 'start_server', 'status' => 'running',
                'progress' => 0, 'started' => '2024-01-01T00:00:00+00:00',
                'finished' => null, 'error' => ['code' => '', 'message' => ''],
            ],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    expect(fn () => $client->actions()->poll(42, maxAttempts: 1, sleep: static fn (float $s): null => null)->wait())
        ->toThrow(RuntimeException::class, 'Action 42 did not complete within 1 attempts');
});

it('polls an action and propagates http errors', function (): void {
    $history = [];

    $client = makeActionsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => [
                'id' => 42, 'command' => 'start_server', 'status' => 'running',
                'progress' => 0, 'started' => '2024-01-01T00:00:00+00:00',
                'finished' => null, 'error' => ['code' => '', 'message' => ''],
            ],
        ], JSON_THROW_ON_ERROR)),
        new Response(500, ['Content-Type' => 'application/json'], json_encode([
            'error' => ['code' => 'server_error', 'message' => 'Internal Server Error'],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    expect(fn () => $client->actions()->poll(42, sleep: static fn (float $s): null => null)->wait())
        ->toThrow(ServerException::class);
});
