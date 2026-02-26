<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Subnet
{
    public function __construct(
        public readonly string $type,
        public readonly string $ipRange,
        public readonly string $networkZone,
        public readonly string $gateway,
        public readonly ?int $vswitchId,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            type: $data['type'],
            /** @phpstan-ignore-next-line */
            ipRange: $data['ip_range'],
            /** @phpstan-ignore-next-line */
            networkZone: $data['network_zone'],
            /** @phpstan-ignore-next-line */
            gateway: $data['gateway'],
            /** @phpstan-ignore-next-line */
            vswitchId: $data['vswitch_id'] ?? null,
        );
    }
}
