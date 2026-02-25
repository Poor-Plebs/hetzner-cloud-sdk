<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class PublicNet
{
    public function __construct(
        public readonly Ipv4 $ipv4,
        public readonly Ipv6 $ipv6,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            ipv4: Ipv4::create($data['ipv4']),
            /** @phpstan-ignore-next-line */
            ipv6: Ipv6::create($data['ipv6']),
        );
    }
}
