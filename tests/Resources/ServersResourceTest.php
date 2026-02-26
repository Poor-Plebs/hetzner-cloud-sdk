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
use PoorPlebs\HetznerCloudSdk\Models\Datacenter;
use PoorPlebs\HetznerCloudSdk\Models\Image;
use PoorPlebs\HetznerCloudSdk\Models\Ipv4;
use PoorPlebs\HetznerCloudSdk\Models\Ipv6;
use PoorPlebs\HetznerCloudSdk\Models\Location;
use PoorPlebs\HetznerCloudSdk\Models\Pagination;
use PoorPlebs\HetznerCloudSdk\Models\Protection;
use PoorPlebs\HetznerCloudSdk\Models\PublicNet;
use PoorPlebs\HetznerCloudSdk\Models\Server;
use PoorPlebs\HetznerCloudSdk\Models\ServerPrivateNet;
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
    Location::class,
    Pagination::class,
    Protection::class,
    PublicNet::class,
    ServerPrivateNet::class,
    ServerType::class,
);

function serverPayload(int $id = 1, string $name = 'my-server'): array
{
    return [
        'id' => $id,
        'name' => $name,
        'status' => 'running',
        'public_net' => [
            'ipv4' => ['ip' => '1.2.3.4', 'blocked' => false, 'dns_ptr' => 'server.example.com', 'id' => 101],
            'ipv6' => ['ip' => '2001:db8::/64', 'blocked' => false, 'dns_ptr' => [['ip' => '2001:db8::1', 'dns_ptr' => 'server.example.com']], 'id' => 102],
            'floating_ips' => [4711, 4712],
            'firewalls' => [['id' => 38, 'status' => 'applied']],
        ],
        'server_type' => [
            'id' => 1,
            'name' => 'cx11',
            'description' => 'CX11',
            'cores' => 1,
            'memory' => 2.0,
            'disk' => 20,
            'cpu_type' => 'shared',
            'storage_type' => 'local',
            'architecture' => 'x86',
            'deprecated' => false,
            'prices' => [['location' => 'fsn1', 'price_hourly' => ['net' => '0.0040', 'gross' => '0.0048']]],
        ],
        'datacenter' => ['id' => 1, 'name' => 'fsn1-dc14', 'description' => 'Falkenstein 1 DC14'],
        'location' => [
            'id' => 1,
            'name' => 'fsn1',
            'description' => 'Falkenstein DC Park 1',
            'city' => 'Falkenstein',
            'country' => 'DE',
            'latitude' => 50.47612,
            'longitude' => 12.370071,
            'network_zone' => 'eu-central',
        ],
        'image' => [
            'id' => 1,
            'name' => 'ubuntu-20.04',
            'type' => 'system',
            'description' => 'Ubuntu 20.04',
            'status' => 'available',
            'architecture' => 'x86',
            'os_flavor' => 'ubuntu',
            'os_version' => '20.04',
            'disk_size' => 10,
            'created' => '2024-01-01T00:00:00+00:00',
            'labels' => [],
            'rapid_deploy' => true,
            'deprecated' => null,
            'image_size' => 2.3,
            'bound_to' => null,
            'created_from' => null,
            'deleted' => null,
            'protection' => ['delete' => false],
        ],
        'labels' => ['env' => 'test'],
        'created' => '2024-01-01T00:00:00+00:00',
        'locked' => false,
        'rescue_enabled' => false,
        'protection' => ['delete' => false, 'rebuild' => false],
        'private_net' => [
            ['network' => 4711, 'ip' => '10.0.1.1', 'alias_ips' => ['10.0.1.2'], 'mac_address' => 'AA:BB:CC:DD:EE:FF'],
        ],
        'volumes' => [1, 2],
        'load_balancers' => [],
        'primary_disk_size' => 20,
        'included_traffic' => 654321,
        'ingoing_traffic' => 123456,
        'outgoing_traffic' => 123456,
        'iso' => null,
        'backup_window' => '22-02',
        'placement_group' => null,
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
        ->and($response->result->locked)->toBeFalse()
        ->and($response->result->rescueEnabled)->toBeFalse()
        ->and($response->result->protection)->toBeInstanceOf(Protection::class)
        ->and($response->result->protection->delete)->toBeFalse()
        ->and($response->result->protection->rebuild)->toBeFalse()
        ->and($response->result->location)->toBeInstanceOf(Location::class)
        ->and($response->result->location->city)->toBe('Falkenstein')
        ->and($response->result->location->country)->toBe('DE')
        ->and($response->result->location->networkZone)->toBe('eu-central')
        ->and($response->result->privateNet)->toHaveCount(1)
        ->and($response->result->privateNet[0])->toBeInstanceOf(ServerPrivateNet::class)
        ->and($response->result->privateNet[0]->ip)->toBe('10.0.1.1')
        ->and($response->result->privateNet[0]->macAddress)->toBe('AA:BB:CC:DD:EE:FF')
        ->and($response->result->privateNet[0]->aliasIps)->toBe(['10.0.1.2'])
        ->and($response->result->volumes)->toBe([1, 2])
        ->and($response->result->primaryDiskSize)->toBe(20)
        ->and($response->result->backupWindow)->toBe('22-02')
        ->and($response->result->publicNet->floatingIps)->toBe([4711, 4712])
        ->and($response->result->publicNet->firewalls)->toHaveCount(1)
        ->and($response->result->publicNet->ipv4->dnsPtr)->toBe('server.example.com')
        ->and($response->result->publicNet->ipv4->id)->toBe(101)
        ->and($response->result->publicNet->ipv6->dnsPtr)->toHaveCount(1)
        ->and($response->result->publicNet->ipv6->id)->toBe(102)
        ->and($response->result->serverType->cores)->toBe(1)
        ->and($response->result->serverType->memory)->toBe(2.0)
        ->and($response->result->serverType->disk)->toBe(20.0)
        ->and($response->result->serverType->cpuType)->toBe('shared')
        ->and($response->result->serverType->architecture)->toBe('x86')
        ->and($response->result->image->architecture)->toBe('x86')
        ->and($response->result->image->osFlavor)->toBe('ubuntu')
        ->and($response->result->image->osVersion)->toBe('20.04')
        ->and($response->result->image->diskSize)->toBe(10.0)
        ->and($response->result->image->rapidDeploy)->toBeTrue()
        ->and($response->result->image->protection)->toBeInstanceOf(Protection::class)
        ->and($response->result->image->protection->delete)->toBeFalse()
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

it('lists all servers from a single page', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'servers' => [serverPayload(1, 'srv-1'), serverPayload(2, 'srv-2')],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 50, 'previous_page' => null,
                'next_page' => null, 'last_page' => 1, 'total_entries' => 2,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->servers()->listAll()->wait();

    expect($result)->toBeArray()->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(Server::class)
        ->and($result[0]->name)->toBe('srv-1')
        ->and($result[1]->name)->toBe('srv-2')
        ->and($history)->toHaveCount(1);
});

it('lists all servers across multiple pages concurrently', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'servers' => [serverPayload(1, 'srv-1')],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 1, 'previous_page' => null,
                'next_page' => 2, 'last_page' => 3, 'total_entries' => 3,
            ]],
        ], JSON_THROW_ON_ERROR)),
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'servers' => [serverPayload(2, 'srv-2')],
            'meta' => ['pagination' => [
                'page' => 2, 'per_page' => 1, 'previous_page' => 1,
                'next_page' => 3, 'last_page' => 3, 'total_entries' => 3,
            ]],
        ], JSON_THROW_ON_ERROR)),
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'servers' => [serverPayload(3, 'srv-3')],
            'meta' => ['pagination' => [
                'page' => 3, 'per_page' => 1, 'previous_page' => 2,
                'next_page' => null, 'last_page' => 3, 'total_entries' => 3,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->servers()->listAll(perPage: 1)->wait();

    expect($result)->toBeArray()->toHaveCount(3)
        ->and($result[0]->name)->toBe('srv-1')
        ->and($result[1]->name)->toBe('srv-2')
        ->and($result[2]->name)->toBe('srv-3')
        ->and($history)->toHaveCount(3);
});

it('lists all servers returns empty array when no servers exist', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'servers' => [],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 50, 'previous_page' => null,
                'next_page' => null, 'last_page' => 1, 'total_entries' => 0,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->servers()->listAll()->wait();

    expect($result)->toBeArray()->toBeEmpty()
        ->and($history)->toHaveCount(1);
});

it('lists all servers defaults to single page when pagination metadata is absent', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'servers' => [serverPayload(1, 'srv-1')],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->servers()->listAll()->wait();

    expect($result)->toBeArray()->toHaveCount(1)
        ->and($result[0]->name)->toBe('srv-1')
        ->and($history)->toHaveCount(1);
});

it('lists all servers propagates exception when a concurrent page request fails', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'servers' => [serverPayload(1, 'srv-1')],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 1, 'previous_page' => null,
                'next_page' => 2, 'last_page' => 2, 'total_entries' => 2,
            ]],
        ], JSON_THROW_ON_ERROR)),
        new Response(500, ['Content-Type' => 'application/json'], json_encode([
            'error' => ['code' => 'server_error', 'message' => 'Internal Server Error'],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    expect(fn () => $client->servers()->listAll(perPage: 1)->wait())
        ->toThrow(ServerException::class);
});

it('lists all servers merges correctly when a later page returns empty', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'servers' => [serverPayload(1, 'srv-1')],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 1, 'previous_page' => null,
                'next_page' => 2, 'last_page' => 2, 'total_entries' => 2,
            ]],
        ], JSON_THROW_ON_ERROR)),
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'servers' => [],
            'meta' => ['pagination' => [
                'page' => 2, 'per_page' => 1, 'previous_page' => 1,
                'next_page' => null, 'last_page' => 2, 'total_entries' => 1,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->servers()->listAll(perPage: 1)->wait();

    expect($result)->toBeArray()->toHaveCount(1)
        ->and($result[0]->name)->toBe('srv-1')
        ->and($history)->toHaveCount(2);
});

it('attaches a server to a network', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => actionPayload(14, 'attach_to_network'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->servers()->attachToNetwork(42, [
        'network' => 4711,
        'ip' => '10.0.1.1',
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result->command)->toBe('attach_to_network')
        ->and($requestPayload)->toMatchArray(['network' => 4711, 'ip' => '10.0.1.1'])
        ->and((string)$history[0]['request']->getUri())->toContain('/servers/42/actions/attach_to_network');
});

it('detaches a server from a network', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => actionPayload(15, 'detach_from_network'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->servers()->detachFromNetwork(42, 4711)->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result->command)->toBe('detach_from_network')
        ->and($requestPayload)->toMatchArray(['network' => 4711])
        ->and((string)$history[0]['request']->getUri())->toContain('/servers/42/actions/detach_from_network');
});

it('changes server protection', function (): void {
    $history = [];

    $client = makeServersClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'action' => actionPayload(16, 'change_protection'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->servers()->changeProtection(42, delete: true, rebuild: true)->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ActionResponse::class)
        ->and($response->result->command)->toBe('change_protection')
        ->and($requestPayload)->toMatchArray(['delete' => true, 'rebuild' => true])
        ->and((string)$history[0]['request']->getUri())->toContain('/servers/42/actions/change_protection');
});
