<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PoorPlebs\HetznerCloudSdk\HetznerCloudClient;
use PoorPlebs\HetznerCloudSdk\Models\Action;
use PoorPlebs\HetznerCloudSdk\Models\ActionError;
use PoorPlebs\HetznerCloudSdk\Models\Datacenter;
use PoorPlebs\HetznerCloudSdk\Models\Image;
use PoorPlebs\HetznerCloudSdk\Models\Ipv4;
use PoorPlebs\HetznerCloudSdk\Models\Ipv6;
use PoorPlebs\HetznerCloudSdk\Models\Pagination;
use PoorPlebs\HetznerCloudSdk\Models\PublicNet;
use PoorPlebs\HetznerCloudSdk\Models\Server;
use PoorPlebs\HetznerCloudSdk\Models\ServerType;
use PoorPlebs\HetznerCloudSdk\Resources\ServersResource;
use PoorPlebs\HetznerCloudSdk\Responses\ActionResponse;
use PoorPlebs\HetznerCloudSdk\Responses\ServerListResponse;
use PoorPlebs\HetznerCloudSdk\Responses\ServerResponse;
use PoorPlebs\HetznerCloudSdk\Tests\Support\InMemoryCache;

covers(
    ServersResource::class,
    ServerListResponse::class,
    ServerResponse::class,
    ActionResponse::class,
    Server::class,
    Action::class,
    ActionError::class,
    Datacenter::class,
    Image::class,
    Ipv4::class,
    Ipv6::class,
    Pagination::class,
    PublicNet::class,
    ServerType::class,
);

function serverPayload(int $id = 1, string $name = 'my-server'): array
{
    return [
        'id' => $id,
        'name' => $name,
        'status' => 'running',
        'public_net' => [
            'ipv4' => ['ip' => '1.2.3.4', 'blocked' => false],
            'ipv6' => ['ip' => '2001:db8::/64', 'blocked' => false],
        ],
        'server_type' => ['id' => 1, 'name' => 'cx11', 'description' => 'CX11'],
        'datacenter' => ['id' => 1, 'name' => 'fsn1-dc14', 'description' => 'Falkenstein 1 DC14'],
        'image' => ['id' => 1, 'name' => 'ubuntu-20.04', 'type' => 'system', 'description' => 'Ubuntu 20.04', 'status' => 'available'],
        'labels' => ['env' => 'test'],
        'created' => '2024-01-01T00:00:00+00:00',
    ];
}

function actionPayload(int $id = 1, string $command = 'start_server'): array
{
    return [
        'id' => $id,
        'command' => $command,
        'status' => 'running',
        'progress' => 0,
        'started' => '2024-01-01T00:00:00+00:00',
        'finished' => null,
        'error' => ['code' => '', 'message' => ''],
    ];
}

/**
 * @param array<int,Response> $responses
 * @param array<int,array<string,mixed>> $history
 */
function makeServersClient(array $responses, array &$history): HetznerCloudClient
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

it('lists servers with pagination query parameters', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'servers' => [serverPayload()],
            'meta' => ['pagination' => [
                'page' => 1,
                'per_page' => 25,
                'previous_page' => null,
                'next_page' => 2,
                'last_page' => 3,
                'total_entries' => 75,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->servers()->list(page: 1, perPage: 25)->wait();

    parse_str($history[0]['request']->getUri()->getQuery(), $query);

    expect($response)->toBeInstanceOf(ServerListResponse::class)
        ->and($response->result)->toBeArray()->toHaveCount(1)
        ->and($response->result[0])->toBeInstanceOf(Server::class)
        ->and($response->result[0]->name)->toBe('my-server')
        ->and($response->pagination)->not->toBeNull()
        ->and($response->pagination->totalEntries)->toBe(75)
        ->and($query)->toMatchArray(['page' => '1', 'per_page' => '25']);
});

it('gets a single server by id', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'server' => serverPayload(42, 'web-1'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->servers()->get(42)->wait();

    expect($response)->toBeInstanceOf(ServerResponse::class)
        ->and($response->result)->toBeInstanceOf(Server::class)
        ->and($response->result->id)->toBe(42)
        ->and($response->result->name)->toBe('web-1')
        ->and((string)$history[0]['request']->getUri())->toContain('/servers/42');
});

it('creates a server with json payload', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(201, ['Content-Type' => 'application/json'], json_encode([
            'server' => serverPayload(99, 'new-server'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->servers()->create([
        'name' => 'new-server',
        'server_type' => 'cx11',
        'image' => 'ubuntu-20.04',
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ServerResponse::class)
        ->and($response->result->name)->toBe('new-server')
        ->and($requestPayload)->toMatchArray([
            'name' => 'new-server',
            'server_type' => 'cx11',
            'image' => 'ubuntu-20.04',
        ]);
});

it('deletes a server and returns an action response', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => actionPayload(10, 'delete_server'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->servers()->delete(42)->wait();

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result)->toBeInstanceOf(Action::class)
        ->and($response->result->command)->toBe('delete_server')
        ->and($history[0]['request']->getMethod())->toBe('DELETE')
        ->and((string)$history[0]['request']->getUri())->toContain('/servers/42');
});

it('powers on a server', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => actionPayload(11, 'start_server'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->servers()->powerOn(42)->wait();

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result->command)->toBe('start_server')
        ->and($history[0]['request']->getMethod())->toBe('POST')
        ->and((string)$history[0]['request']->getUri())->toContain('/servers/42/actions/poweron');
});

it('powers off a server', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => actionPayload(12, 'stop_server'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->servers()->powerOff(42)->wait();

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result->command)->toBe('stop_server')
        ->and((string)$history[0]['request']->getUri())->toContain('/servers/42/actions/poweroff');
});

it('rebuilds a server with an image', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => actionPayload(13, 'rebuild_server'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->servers()->rebuild(42, 'ubuntu-22.04')->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result->command)->toBe('rebuild_server')
        ->and($requestPayload)->toMatchArray(['image' => 'ubuntu-22.04'])
        ->and((string)$history[0]['request']->getUri())->toContain('/servers/42/actions/rebuild');
});
