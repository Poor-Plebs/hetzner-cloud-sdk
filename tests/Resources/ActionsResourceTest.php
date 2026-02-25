<?php

declare(strict_types=1);

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
