<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\RequestOptions;
use PoorPlebs\HetznerCloudSdk\Responses\ActionResponse;
use PoorPlebs\HetznerCloudSdk\Responses\NetworkListResponse;
use PoorPlebs\HetznerCloudSdk\Responses\NetworkResponse;

class NetworksResource
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    /**
     * @param array<string,mixed> $route
     */
    public function addRoute(int $id, array $route): PromiseInterface
    {
        return $this->client
            ->postAsync("networks/{$id}/actions/add_route", [
                RequestOptions::JSON => $route,
            ])
            ->then(ActionResponse::make(...));
    }

    /**
     * @param array<string,mixed> $subnet
     */
    public function addSubnet(int $id, array $subnet): PromiseInterface
    {
        return $this->client
            ->postAsync("networks/{$id}/actions/add_subnet", [
                RequestOptions::JSON => $subnet,
            ])
            ->then(ActionResponse::make(...));
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function changeProtection(int $id, array $payload): PromiseInterface
    {
        return $this->client
            ->postAsync("networks/{$id}/actions/change_protection", [
                RequestOptions::JSON => $payload,
            ])
            ->then(ActionResponse::make(...));
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function create(array $payload): PromiseInterface
    {
        return $this->client
            ->postAsync('networks', [
                RequestOptions::JSON => $payload,
            ])
            ->then(NetworkResponse::make(...));
    }

    public function delete(int $id): PromiseInterface
    {
        return $this->client
            ->deleteAsync("networks/{$id}");
    }

    /**
     * @param array<string,mixed> $route
     */
    public function deleteRoute(int $id, array $route): PromiseInterface
    {
        return $this->client
            ->postAsync("networks/{$id}/actions/delete_route", [
                RequestOptions::JSON => $route,
            ])
            ->then(ActionResponse::make(...));
    }

    /**
     * @param array<string,mixed> $subnet
     */
    public function deleteSubnet(int $id, array $subnet): PromiseInterface
    {
        return $this->client
            ->postAsync("networks/{$id}/actions/delete_subnet", [
                RequestOptions::JSON => $subnet,
            ])
            ->then(ActionResponse::make(...));
    }

    public function get(int $id): PromiseInterface
    {
        return $this->client
            ->getAsync("networks/{$id}")
            ->then(NetworkResponse::make(...));
    }

    public function list(int $page = 1, int $perPage = 25): PromiseInterface
    {
        return $this->client
            ->getAsync('networks', [
                RequestOptions::QUERY => [
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ])
            ->then(NetworkListResponse::make(...));
    }

    public function listAll(int $perPage = 50): PromiseInterface
    {
        return $this->list(1, $perPage)->then(function (NetworkListResponse $first) use ($perPage): PromiseInterface|array {
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
                    /** @var array<int,NetworkListResponse> $responses */
                    foreach ($responses as $response) {
                        $results = array_merge($results, $response->result);
                    }

                    return $results;
                },
            );
        });
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function update(int $id, array $payload): PromiseInterface
    {
        return $this->client
            ->putAsync("networks/{$id}", [
                RequestOptions::JSON => $payload,
            ])
            ->then(NetworkResponse::make(...));
    }
}
