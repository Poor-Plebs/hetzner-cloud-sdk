<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class ServerPrivateNet
{
    /**
     * @param array<int,string> $aliasIps
     */
    public function __construct(
        public readonly int $network,
        public readonly string $ip,
        public readonly array $aliasIps,
        public readonly string $macAddress,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            network: $data['network'],
            /** @phpstan-ignore-next-line */
            ip: $data['ip'],
            /** @phpstan-ignore-next-line */
            aliasIps: $data['alias_ips'] ?? [],
            /** @phpstan-ignore-next-line */
            macAddress: $data['mac_address'],
        );
    }
}
