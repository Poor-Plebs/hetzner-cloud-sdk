<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use PoorPlebs\HetznerCloudSdk\Responses\ActionResponse;
use PoorPlebs\HetznerCloudSdk\Responses\ServerListResponse;
use PoorPlebs\HetznerCloudSdk\Responses\ServerResponse;

class ServersResource
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function create(array $payload): PromiseInterface
    {
        return $this->client
            ->postAsync('servers', [
                RequestOptions::JSON => $payload,
            ])
            ->then(ServerResponse::make(...));
    }

    public function delete(int $id): PromiseInterface
    {
        return $this->client
            ->deleteAsync("servers/{$id}")
            ->then(ActionResponse::make(...));
    }

    public function get(int $id): PromiseInterface
    {
        return $this->client
            ->getAsync("servers/{$id}")
            ->then(ServerResponse::make(...));
    }

    public function list(int $page = 1, int $perPage = 25): PromiseInterface
    {
        return $this->client
            ->getAsync('servers', [
                RequestOptions::QUERY => [
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ])
            ->then(ServerListResponse::make(...));
    }

    public function powerOff(int $id): PromiseInterface
    {
        return $this->client
            ->postAsync("servers/{$id}/actions/poweroff")
            ->then(ActionResponse::make(...));
    }

    public function powerOn(int $id): PromiseInterface
    {
        return $this->client
            ->postAsync("servers/{$id}/actions/poweron")
            ->then(ActionResponse::make(...));
    }

    public function rebuild(int $id, string $image): PromiseInterface
    {
        return $this->client
            ->postAsync("servers/{$id}/actions/rebuild", [
                RequestOptions::JSON => [
                    'image' => $image,
                ],
            ])
            ->then(ActionResponse::make(...));
    }
}
