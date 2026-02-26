<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
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
    public function attachToNetwork(int $id, array $payload): PromiseInterface
    {
        return $this->client
            ->postAsync("servers/{$id}/actions/attach_to_network", [
                RequestOptions::JSON => $payload,
            ])
            ->then(ActionResponse::make(...));
    }

    public function changeProtection(int $id, bool $delete, bool $rebuild): PromiseInterface
    {
        return $this->client
            ->postAsync("servers/{$id}/actions/change_protection", [
                RequestOptions::JSON => [
                    'delete' => $delete,
                    'rebuild' => $rebuild,
                ],
            ])
            ->then(ActionResponse::make(...));
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

    public function detachFromNetwork(int $id, int $networkId): PromiseInterface
    {
        return $this->client
            ->postAsync("servers/{$id}/actions/detach_from_network", [
                RequestOptions::JSON => [
                    'network' => $networkId,
                ],
            ])
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

    public function listAll(int $perPage = 50): PromiseInterface
    {
        return $this->list(1, $perPage)->then(function (ServerListResponse $first) use ($perPage): PromiseInterface|array {
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
                    /** @var array<int,ServerListResponse> $responses */
                    foreach ($responses as $response) {
                        $results = array_merge($results, $response->result);
                    }

                    return $results;
                },
            );
        });
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
