<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Responses;

use PoorPlebs\HetznerCloudSdk\Models\Firewall;

class FirewallListResponse extends HetznerResponse
{
    /**
     * @var array<int,Firewall>
     */
    public readonly mixed $result; // @phpstan-ignore-line

    /**
     * @param array<string,mixed> $data
     */
    protected function init(array $data): void
    {
        /** @var array<int,array<string,mixed>> $firewalls */
        $firewalls = $data['firewalls'] ?? [];

        /** @phpstan-ignore-next-line */
        $this->result = array_map(
            static fn (array $firewall): Firewall => Firewall::create($firewall),
            $firewalls,
        );
    }
}
