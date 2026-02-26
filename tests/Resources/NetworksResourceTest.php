<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PoorPlebs\HetznerCloudSdk\HetznerCloudClient;
use PoorPlebs\HetznerCloudSdk\Models\Action;
use PoorPlebs\HetznerCloudSdk\Models\Network;
use PoorPlebs\HetznerCloudSdk\Models\Protection;
use PoorPlebs\HetznerCloudSdk\Models\Route;
use PoorPlebs\HetznerCloudSdk\Models\Subnet;
use PoorPlebs\HetznerCloudSdk\Resources\NetworksResource;
use PoorPlebs\HetznerCloudSdk\Responses\ActionResponse;
use PoorPlebs\HetznerCloudSdk\Responses\NetworkListResponse;
use PoorPlebs\HetznerCloudSdk\Responses\NetworkResponse;
use PoorPlebs\HetznerCloudSdk\Tests\Support\InMemoryCache;

covers(
    NetworksResource::class,
    NetworkListResponse::class,
    NetworkResponse::class,
    ActionResponse::class,
    Network::class,
    Subnet::class,
    Route::class,
    Protection::class,
);

function networkPayload(int $id = 1, string $name = 'my-network'): array
{
    return [
        'id' => $id,
        'name' => $name,
        'ip_range' => '10.0.0.0/16',
        'subnets' => [
            [
                'type' => 'cloud',
                'ip_range' => '10.0.1.0/24',
                'network_zone' => 'eu-central',
                'gateway' => '10.0.0.1',
                'vswitch_id' => null,
            ],
        ],
        'routes' => [
            ['destination' => '10.100.1.0/24', 'gateway' => '10.0.1.1'],
        ],
        'servers' => [42, 43],
        'load_balancers' => [],
        'protection' => ['delete' => false],
        'labels' => ['env' => 'test'],
        'created' => '2024-01-01T00:00:00+00:00',
        'expose_routes_to_vswitch' => false,
    ];
}

function networkActionPayload(int $id = 1, string $command = 'add_subnet'): array
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
function makeNetworksClient(array $responses, array &$history): HetznerCloudClient
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

it('lists networks with pagination', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'networks' => [networkPayload()],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 25, 'previous_page' => null,
                'next_page' => null, 'last_page' => 1, 'total_entries' => 1,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->networks()->list()->wait();

    expect($response)->toBeInstanceOf(NetworkListResponse::class)
        ->and($response->result)->toBeArray()->toHaveCount(1)
        ->and($response->result[0])->toBeInstanceOf(Network::class)
        ->and($response->result[0]->name)->toBe('my-network')
        ->and($response->result[0]->ipRange)->toBe('10.0.0.0/16')
        ->and($response->result[0]->subnets)->toHaveCount(1)
        ->and($response->result[0]->subnets[0])->toBeInstanceOf(Subnet::class)
        ->and($response->result[0]->subnets[0]->type)->toBe('cloud')
        ->and($response->result[0]->subnets[0]->ipRange)->toBe('10.0.1.0/24')
        ->and($response->result[0]->subnets[0]->networkZone)->toBe('eu-central')
        ->and($response->result[0]->subnets[0]->gateway)->toBe('10.0.0.1')
        ->and($response->result[0]->routes)->toHaveCount(1)
        ->and($response->result[0]->routes[0])->toBeInstanceOf(Route::class)
        ->and($response->result[0]->routes[0]->destination)->toBe('10.100.1.0/24')
        ->and($response->result[0]->routes[0]->gateway)->toBe('10.0.1.1')
        ->and($response->result[0]->servers)->toBe([42, 43])
        ->and($response->result[0]->protection)->toBeInstanceOf(Protection::class)
        ->and($response->result[0]->protection->delete)->toBeFalse()
        ->and($response->result[0]->labels)->toBe(['env' => 'test']);
});

it('gets a single network by id', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'network' => networkPayload(5, 'web-net'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->networks()->get(5)->wait();

    expect($response)->toBeInstanceOf(NetworkResponse::class)
        ->and($response->result)->toBeInstanceOf(Network::class)
        ->and($response->result->id)->toBe(5)
        ->and($response->result->name)->toBe('web-net')
        ->and((string)$history[0]['request']->getUri())->toContain('/networks/5');
});

it('creates a network', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(201, ['Content-Type' => 'application/json'], json_encode([
            'network' => networkPayload(10, 'new-net'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->networks()->create([
        'name' => 'new-net',
        'ip_range' => '10.0.0.0/16',
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(NetworkResponse::class)
        ->and($response->result->name)->toBe('new-net')
        ->and($requestPayload)->toMatchArray([
            'name' => 'new-net',
            'ip_range' => '10.0.0.0/16',
        ]);
});

it('updates a network', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'network' => networkPayload(5, 'updated-net'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->networks()->update(5, [
        'name' => 'updated-net',
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(NetworkResponse::class)
        ->and($response->result->name)->toBe('updated-net')
        ->and($requestPayload)->toMatchArray(['name' => 'updated-net'])
        ->and($history[0]['request']->getMethod())->toBe('PUT')
        ->and((string)$history[0]['request']->getUri())->toContain('/networks/5');
});

it('deletes a network', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(204, [], ''),
    ], $history);

    $client->networks()->delete(5)->wait();

    expect($history[0]['request']->getMethod())->toBe('DELETE')
        ->and((string)$history[0]['request']->getUri())->toContain('/networks/5');
});

it('adds a subnet to a network', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => networkActionPayload(1, 'add_subnet'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->networks()->addSubnet(5, [
        'type' => 'cloud',
        'ip_range' => '10.0.2.0/24',
        'network_zone' => 'eu-central',
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result)->toBeInstanceOf(Action::class)
        ->and($response->result->command)->toBe('add_subnet')
        ->and($requestPayload)->toMatchArray(['type' => 'cloud', 'ip_range' => '10.0.2.0/24'])
        ->and((string)$history[0]['request']->getUri())->toContain('/networks/5/actions/add_subnet');
});

it('deletes a subnet from a network', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => networkActionPayload(2, 'delete_subnet'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->networks()->deleteSubnet(5, [
        'ip_range' => '10.0.2.0/24',
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result->command)->toBe('delete_subnet')
        ->and($requestPayload)->toMatchArray(['ip_range' => '10.0.2.0/24'])
        ->and((string)$history[0]['request']->getUri())->toContain('/networks/5/actions/delete_subnet');
});

it('adds a route to a network', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => networkActionPayload(3, 'add_route'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->networks()->addRoute(5, [
        'destination' => '10.100.1.0/24',
        'gateway' => '10.0.1.1',
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result->command)->toBe('add_route')
        ->and($requestPayload)->toMatchArray(['destination' => '10.100.1.0/24', 'gateway' => '10.0.1.1'])
        ->and((string)$history[0]['request']->getUri())->toContain('/networks/5/actions/add_route');
});

it('deletes a route from a network', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => networkActionPayload(4, 'delete_route'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->networks()->deleteRoute(5, [
        'destination' => '10.100.1.0/24',
        'gateway' => '10.0.1.1',
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result->command)->toBe('delete_route')
        ->and($requestPayload)->toMatchArray(['destination' => '10.100.1.0/24', 'gateway' => '10.0.1.1'])
        ->and((string)$history[0]['request']->getUri())->toContain('/networks/5/actions/delete_route');
});

it('changes network protection', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => networkActionPayload(5, 'change_protection'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->networks()->changeProtection(5, ['delete' => true])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result->command)->toBe('change_protection')
        ->and($requestPayload)->toMatchArray(['delete' => true])
        ->and((string)$history[0]['request']->getUri())->toContain('/networks/5/actions/change_protection');
});

it('lists all networks from a single page', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'networks' => [networkPayload(1, 'net-1'), networkPayload(2, 'net-2')],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 50, 'previous_page' => null,
                'next_page' => null, 'last_page' => 1, 'total_entries' => 2,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->networks()->listAll()->wait();

    expect($result)->toBeArray()->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(Network::class)
        ->and($result[0]->name)->toBe('net-1')
        ->and($result[1]->name)->toBe('net-2')
        ->and($history)->toHaveCount(1);
});

it('lists all networks across multiple pages concurrently', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'networks' => [networkPayload(1, 'net-1')],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 1, 'previous_page' => null,
                'next_page' => 2, 'last_page' => 3, 'total_entries' => 3,
            ]],
        ], JSON_THROW_ON_ERROR)),
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'networks' => [networkPayload(2, 'net-2')],
            'meta' => ['pagination' => [
                'page' => 2, 'per_page' => 1, 'previous_page' => 1,
                'next_page' => 3, 'last_page' => 3, 'total_entries' => 3,
            ]],
        ], JSON_THROW_ON_ERROR)),
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'networks' => [networkPayload(3, 'net-3')],
            'meta' => ['pagination' => [
                'page' => 3, 'per_page' => 1, 'previous_page' => 2,
                'next_page' => null, 'last_page' => 3, 'total_entries' => 3,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->networks()->listAll(perPage: 1)->wait();

    expect($result)->toBeArray()->toHaveCount(3)
        ->and($result[0]->name)->toBe('net-1')
        ->and($result[1]->name)->toBe('net-2')
        ->and($result[2]->name)->toBe('net-3')
        ->and($history)->toHaveCount(3);
});

it('lists all networks returns empty array when no networks exist', function (): void {
    $history = [];

    $client = makeNetworksClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'networks' => [],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 50, 'previous_page' => null,
                'next_page' => null, 'last_page' => 1, 'total_entries' => 0,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->networks()->listAll()->wait();

    expect($result)->toBeArray()->toBeEmpty()
        ->and($history)->toHaveCount(1);
});
