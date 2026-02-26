<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\RequestOptions;
use PoorPlebs\HetznerCloudSdk\Responses\ActionListResponse;
use PoorPlebs\HetznerCloudSdk\Responses\FirewallListResponse;
use PoorPlebs\HetznerCloudSdk\Responses\FirewallResponse;

class FirewallsResource
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    /**
     * @param array<int,array<string,mixed>> $resources
     */
    public function applyToResources(int $id, array $resources): PromiseInterface
    {
        return $this->client
            ->postAsync("firewalls/{$id}/actions/apply_to_resources", [
                RequestOptions::JSON => [
                    'apply_to' => $resources,
                ],
            ])
            ->then(ActionListResponse::make(...));
    }

    /**
     * @param array<int,array<string,mixed>> $rules
     * @param array<string,string> $labels
     */
    public function create(string $name, array $rules = [], array $labels = []): PromiseInterface
    {
        return $this->client
            ->postAsync('firewalls', [
                RequestOptions::JSON => array_filter([
                    'name' => $name,
                    'rules' => $rules,
                    'labels' => $labels,
                ], static fn (mixed $value): bool => $value !== []),
            ])
            ->then(FirewallResponse::make(...));
    }

    public function delete(int $id): PromiseInterface
    {
        return $this->client
            ->deleteAsync("firewalls/{$id}");
    }

    public function get(int $id): PromiseInterface
    {
        return $this->client
            ->getAsync("firewalls/{$id}")
            ->then(FirewallResponse::make(...));
    }

    public function list(int $page = 1, int $perPage = 25): PromiseInterface
    {
        return $this->client
            ->getAsync('firewalls', [
                RequestOptions::QUERY => [
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ])
            ->then(FirewallListResponse::make(...));
    }

    public function listAll(int $perPage = 50): PromiseInterface
    {
        return $this->list(1, $perPage)->then(function (FirewallListResponse $first) use ($perPage): PromiseInterface|array {
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
                    /** @var array<int,FirewallListResponse> $responses */
                    foreach ($responses as $response) {
                        $results = array_merge($results, $response->result);
                    }

                    return $results;
                },
            );
        });
    }

    /**
     * @param array<int,array<string,mixed>> $resources
     */
    public function removeFromResources(int $id, array $resources): PromiseInterface
    {
        return $this->client
            ->postAsync("firewalls/{$id}/actions/remove_from_resources", [
                RequestOptions::JSON => [
                    'remove_from' => $resources,
                ],
            ])
            ->then(ActionListResponse::make(...));
    }

    /**
     * @param array<int,array<string,mixed>> $rules
     */
    public function setRules(int $id, array $rules): PromiseInterface
    {
        return $this->client
            ->postAsync("firewalls/{$id}/actions/set_rules", [
                RequestOptions::JSON => [
                    'rules' => $rules,
                ],
            ])
            ->then(ActionListResponse::make(...));
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function update(int $id, array $payload): PromiseInterface
    {
        return $this->client
            ->putAsync("firewalls/{$id}", [
                RequestOptions::JSON => $payload,
            ])
            ->then(FirewallResponse::make(...));
    }
}
