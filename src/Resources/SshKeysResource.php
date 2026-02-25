<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use PoorPlebs\HetznerCloudSdk\Responses\SshKeyListResponse;
use PoorPlebs\HetznerCloudSdk\Responses\SshKeyResponse;

class SshKeysResource
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    /**
     * @param array<string,string> $labels
     */
    public function create(string $name, string $publicKey, array $labels = []): PromiseInterface
    {
        return $this->client
            ->postAsync('ssh_keys', [
                RequestOptions::JSON => array_filter([
                    'name' => $name,
                    'public_key' => $publicKey,
                    'labels' => $labels,
                ], static fn (mixed $value): bool => $value !== []),
            ])
            ->then(SshKeyResponse::make(...));
    }

    public function delete(int $id): PromiseInterface
    {
        return $this->client
            ->deleteAsync("ssh_keys/{$id}");
    }

    public function get(int $id): PromiseInterface
    {
        return $this->client
            ->getAsync("ssh_keys/{$id}")
            ->then(SshKeyResponse::make(...));
    }

    public function list(int $page = 1, int $perPage = 25): PromiseInterface
    {
        return $this->client
            ->getAsync('ssh_keys', [
                RequestOptions::QUERY => [
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ])
            ->then(SshKeyListResponse::make(...));
    }
}
