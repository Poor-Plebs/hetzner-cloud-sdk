<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
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

    public function listAll(int $perPage = 50): PromiseInterface
    {
        return $this->list(1, $perPage)->then(function (SshKeyListResponse $first) use ($perPage): PromiseInterface|array {
            $results = $first->result;
            $lastPage = $first->pagination !== null ? $first->pagination->lastPage : 1;

            if ($lastPage <= 1) {
                return $results;
            }

            $promises = [];
            for ($page = 2; $page <= $lastPage; $page++) {
                $promises[] = $this->list($page, $perPage);
            }

            return Utils::all($promises)->then(
                static function (array $responses) use ($results): array {
                    /** @var array<int,SshKeyListResponse> $responses */
                    foreach ($responses as $response) {
                        $results = array_merge($results, $response->result);
                    }

                    return $results;
                },
            );
        });
    }
}
