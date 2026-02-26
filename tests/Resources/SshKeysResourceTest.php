<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PoorPlebs\HetznerCloudSdk\HetznerCloudClient;
use PoorPlebs\HetznerCloudSdk\Models\SshKey;
use PoorPlebs\HetznerCloudSdk\Resources\SshKeysResource;
use PoorPlebs\HetznerCloudSdk\Responses\SshKeyListResponse;
use PoorPlebs\HetznerCloudSdk\Responses\SshKeyResponse;
use PoorPlebs\HetznerCloudSdk\Tests\Support\InMemoryCache;

covers(
    SshKeysResource::class,
    SshKeyListResponse::class,
    SshKeyResponse::class,
    SshKey::class,
);

function sshKeyPayload(int $id = 1, string $name = 'my-key'): array
{
    return [
        'id' => $id,
        'name' => $name,
        'public_key' => 'ssh-rsa AAAA...',
        'fingerprint' => 'b7:2f:30:a0:2f:6c:58:6c:21:04:58:61:ba:06:3b:2f',
        'labels' => ['env' => 'test'],
        'created' => '2024-01-01T00:00:00+00:00',
    ];
}

/**
 * @param array<int,Response> $responses
 * @param array<int,array<string,mixed>> $history
 */
function makeSshKeysClient(array $responses, array &$history): HetznerCloudClient
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

it('lists ssh keys with pagination', function (): void {
    $history = [];

    $client = makeSshKeysClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'ssh_keys' => [sshKeyPayload()],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 25, 'previous_page' => null,
                'next_page' => null, 'last_page' => 1, 'total_entries' => 1,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->sshKeys()->list()->wait();

    expect($response)->toBeInstanceOf(SshKeyListResponse::class)
        ->and($response->result)->toBeArray()->toHaveCount(1)
        ->and($response->result[0])->toBeInstanceOf(SshKey::class)
        ->and($response->result[0]->name)->toBe('my-key')
        ->and($response->result[0]->fingerprint)->toBe('b7:2f:30:a0:2f:6c:58:6c:21:04:58:61:ba:06:3b:2f');
});

it('gets a single ssh key by id', function (): void {
    $history = [];

    $client = makeSshKeysClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'ssh_key' => sshKeyPayload(7, 'deploy-key'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->sshKeys()->get(7)->wait();

    expect($response)->toBeInstanceOf(SshKeyResponse::class)
        ->and($response->result->id)->toBe(7)
        ->and($response->result->name)->toBe('deploy-key')
        ->and((string)$history[0]['request']->getUri())->toContain('/ssh_keys/7');
});

it('creates an ssh key with name and public key', function (): void {
    $history = [];

    $client = makeSshKeysClient([
        new Response(201, ['Content-Type' => 'application/json'], json_encode([
            'ssh_key' => sshKeyPayload(10, 'new-key'),
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $response = $client->sshKeys()->create('new-key', 'ssh-rsa AAAA...')->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(SshKeyResponse::class)
        ->and($response->result->name)->toBe('new-key')
        ->and($requestPayload['name'])->toBe('new-key')
        ->and($requestPayload['public_key'])->toBe('ssh-rsa AAAA...');
});

it('deletes an ssh key', function (): void {
    $history = [];

    $client = makeSshKeysClient([
        new Response(204, [], ''),
    ], $history);

    $client->sshKeys()->delete(7)->wait();

    expect($history[0]['request']->getMethod())->toBe('DELETE')
        ->and((string)$history[0]['request']->getUri())->toContain('/ssh_keys/7');
});

it('lists all ssh keys from a single page', function (): void {
    $history = [];

    $client = makeSshKeysClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'ssh_keys' => [sshKeyPayload(1, 'key-1'), sshKeyPayload(2, 'key-2')],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 50, 'previous_page' => null,
                'next_page' => null, 'last_page' => 1, 'total_entries' => 2,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->sshKeys()->listAll()->wait();

    expect($result)->toBeArray()->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(SshKey::class)
        ->and($result[0]->name)->toBe('key-1')
        ->and($result[1]->name)->toBe('key-2')
        ->and($history)->toHaveCount(1);
});

it('lists all ssh keys across multiple pages concurrently', function (): void {
    $history = [];

    $client = makeSshKeysClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'ssh_keys' => [sshKeyPayload(1, 'key-1')],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 1, 'previous_page' => null,
                'next_page' => 2, 'last_page' => 3, 'total_entries' => 3,
            ]],
        ], JSON_THROW_ON_ERROR)),
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'ssh_keys' => [sshKeyPayload(2, 'key-2')],
            'meta' => ['pagination' => [
                'page' => 2, 'per_page' => 1, 'previous_page' => 1,
                'next_page' => 3, 'last_page' => 3, 'total_entries' => 3,
            ]],
        ], JSON_THROW_ON_ERROR)),
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'ssh_keys' => [sshKeyPayload(3, 'key-3')],
            'meta' => ['pagination' => [
                'page' => 3, 'per_page' => 1, 'previous_page' => 2,
                'next_page' => null, 'last_page' => 3, 'total_entries' => 3,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->sshKeys()->listAll(perPage: 1)->wait();

    expect($result)->toBeArray()->toHaveCount(3)
        ->and($result[0]->name)->toBe('key-1')
        ->and($result[1]->name)->toBe('key-2')
        ->and($result[2]->name)->toBe('key-3')
        ->and($history)->toHaveCount(3);
});

it('lists all ssh keys returns empty array when no keys exist', function (): void {
    $history = [];

    $client = makeSshKeysClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'ssh_keys' => [],
            'meta' => ['pagination' => [
                'page' => 1, 'per_page' => 50, 'previous_page' => null,
                'next_page' => null, 'last_page' => 1, 'total_entries' => 0,
            ]],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->sshKeys()->listAll()->wait();

    expect($result)->toBeArray()->toBeEmpty()
        ->and($history)->toHaveCount(1);
});
