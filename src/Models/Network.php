<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Network
{
    /**
     * @param array<int,Subnet> $subnets
     * @param array<int,Route> $routes
     * @param array<int,int> $servers
     * @param array<int,int> $loadBalancers
     * @param array<string,string> $labels
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $ipRange,
        public readonly array $subnets,
        public readonly array $routes,
        public readonly array $servers,
        public readonly array $loadBalancers,
        public readonly ?Protection $protection,
        public readonly array $labels,
        public readonly string $created,
        public readonly bool $exposeRoutesToVswitch,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        /** @var array<int,array<string,mixed>> $subnetsData */
        $subnetsData = $data['subnets'] ?? [];

        /** @var array<int,array<string,mixed>> $routesData */
        $routesData = $data['routes'] ?? [];

        return new self(
            /** @phpstan-ignore-next-line */
            id: $data['id'],
            /** @phpstan-ignore-next-line */
            name: $data['name'],
            /** @phpstan-ignore-next-line */
            ipRange: $data['ip_range'],
            subnets: array_map(
                static fn (array $subnet): Subnet => Subnet::create($subnet),
                $subnetsData,
            ),
            routes: array_map(
                static fn (array $route): Route => Route::create($route),
                $routesData,
            ),
            /** @phpstan-ignore-next-line */
            servers: $data['servers'] ?? [],
            /** @phpstan-ignore-next-line */
            loadBalancers: $data['load_balancers'] ?? [],
            protection: isset($data['protection']) && is_array($data['protection']) ? Protection::create($data['protection']) : null, /** @phpstan-ignore-line */
            /** @phpstan-ignore-next-line */
            labels: $data['labels'] ?? [],
            /** @phpstan-ignore-next-line */
            created: $data['created'],
            /** @phpstan-ignore-next-line */
            exposeRoutesToVswitch: $data['expose_routes_to_vswitch'] ?? false,
        );
    }
}
