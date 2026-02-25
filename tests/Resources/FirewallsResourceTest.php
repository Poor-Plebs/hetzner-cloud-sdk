<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PoorPlebs\HetznerCloudSdk\HetznerCloudClient;
use PoorPlebs\HetznerCloudSdk\Models\Firewall;
use PoorPlebs\HetznerCloudSdk\Models\FirewallResource;
use PoorPlebs\HetznerCloudSdk\Models\FirewallRule;
use PoorPlebs\HetznerCloudSdk\Resources\FirewallsResource;
use PoorPlebs\HetznerCloudSdk\Responses\ActionListResponse;
use PoorPlebs\HetznerCloudSdk\Responses\FirewallListResponse;
use PoorPlebs\HetznerCloudSdk\Responses\FirewallResponse;
use PoorPlebs\HetznerCloudSdk\Tests\Support\InMemoryCache;

covers(
    FirewallsResource::class,
    FirewallListResponse::class,
    FirewallResponse::class,
    ActionListResponse::class,
    Firewall::class,
    FirewallRule::class,
    FirewallResource::class,
);

function firewallPayload(int $id = 1, string $name = 'my-firewall'): array
{
    return [
        'id' => $id,
        'name' => $name,
        'labels' => ['env' => 'test'],
        'rules' => [
            [
                'direction' => 'in',
                'protocol' => 'tcp',
                'port' => '80',
                'source_ips' => ['0.0.0.0/0', '::/0'],
                'destination_ips' => [],
                'description' => 'Allow HTTP',
            ],
        ],
        'applied_to' => [
            ['type' => 'server', 'server' => ['id' => 42]],
        ],
        'created' => '2024-01-01T00:00:00+00:00',
    ];
}

/**
 * @param array<int,Response> $responses
 * @param array<int,array<string,mixed>> $history
 */
function makeFirewallsClient(array $responses, array &$history): HetznerCloudClient
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

it('lists firewalls with pagination', function (): void {
    $history = [];

    $client = makeFirewallsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'firewalls' => [firewallPayload()],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 25, 'previous_page' => null,
                'next_page' => null, 'last_page' => 1, 'total_entries' => 1,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->firewalls()->list()->wait();

    expect($response)->toBeInstanceOf(FirewallListResponse::class)
        ->and($response->result)->toBeArray()->toHaveCount(1)
        ->and($response->result[0])->toBeInstanceOf(Firewall::class)
        ->and($response->result[0]->name)->toBe('my-firewall')
        ->and($response->result[0]->rules)->toHaveCount(1)
        ->and($response->result[0]->rules[0]->port)->toBe('80')
        ->and($response->result[0]->appliedTo)->toHaveCount(1);
});

it('gets a single firewall by id', function (): void {
    $history = [];

    $client = makeFirewallsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'firewall' => firewallPayload(5, 'web-fw'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->firewalls()->get(5)->wait();

    expect($response)->toBeInstanceOf(FirewallResponse::class)
        ->and($response->result->id)->toBe(5)
        ->and($response->result->name)->toBe('web-fw')
        ->and((string)$history[0]['request']->getUri())->toContain('/firewalls/5');
});

it('creates a firewall with name and rules', function (): void {
    $history = [];

    $client = makeFirewallsClient([
        new Response(201, ['Content-Type' => 'application/json'], json_encode([
            'firewall' => firewallPayload(10, 'new-fw'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->firewalls()->create('new-fw', [
        ['direction' => 'in', 'protocol' => 'tcp', 'port' => '443', 'source_ips' => ['0.0.0.0/0']],
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(FirewallResponse::class)
        ->and($response->result->name)->toBe('new-fw')
        ->and($requestPayload['name'])->toBe('new-fw')
        ->and($requestPayload['rules'])->toHaveCount(1);
});

it('deletes a firewall', function (): void {
    $history = [];

    $client = makeFirewallsClient([
        new Response(204, [], ''),
    ], $history);

    $client->firewalls()->delete(5)->wait();

    expect($history[0]['request']->getMethod())->toBe('DELETE')
        ->and((string)$history[0]['request']->getUri())->toContain('/firewalls/5');
});

it('sets rules on a firewall', function (): void {
    $history = [];

    $client = makeFirewallsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'actions' => [
                ['id' => 1, 'command' => 'set_firewall_rules', 'status' => 'running', 'progress' => 0, 'started' => '2024-01-01T00:00:00+00:00', 'finished' => null, 'error' => ['code' => '', 'message' => '']],
            ],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->firewalls()->setRules(5, [
        ['direction' => 'in', 'protocol' => 'tcp', 'port' => '22', 'source_ips' => ['10.0.0.0/8']],
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ActionListResponse::class)
        ->and($response->result)->toHaveCount(1)
        ->and($requestPayload['rules'])->toHaveCount(1)
        ->and((string)$history[0]['request']->getUri())->toContain('/firewalls/5/actions/set_rules');
});

it('applies firewall to resources', function (): void {
    $history = [];

    $client = makeFirewallsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'actions' => [
                ['id' => 2, 'command' => 'apply_firewall', 'status' => 'running', 'progress' => 0, 'started' => '2024-01-01T00:00:00+00:00', 'finished' => null, 'error' => ['code' => '', 'message' => '']],
            ],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->firewalls()->applyToResources(5, [
        ['type' => 'server', 'server' => ['id' => 42]],
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ActionListResponse::class)
        ->and($requestPayload['apply_to'])->toHaveCount(1)
        ->and((string)$history[0]['request']->getUri())->toContain('/firewalls/5/actions/apply_to_resources');
});

it('removes firewall from resources', function (): void {
    $history = [];

    $client = makeFirewallsClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'actions' => [
                ['id' => 3, 'command' => 'remove_firewall', 'status' => 'running', 'progress' => 0, 'started' => '2024-01-01T00:00:00+00:00', 'finished' => null, 'error' => ['code' => '', 'message' => '']],
            ],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->firewalls()->removeFromResources(5, [
        ['type' => 'server', 'server' => ['id' => 42]],
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(ActionListResponse::class)
        ->and($requestPayload['remove_from'])->toHaveCount(1)
        ->and((string)$history[0]['request']->getUri())->toContain('/firewalls/5/actions/remove_from_resources');
});
