<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class FirewallRule
{
    /**
     * @param array<int,string> $sourceIps
     * @param array<int,string> $destinationIps
     */
    public function __construct(
        public readonly string $direction,
        public readonly string $protocol,
        public readonly ?string $port,
        public readonly array $sourceIps,
        public readonly array $destinationIps,
        public readonly ?string $description,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            direction: $data['direction'],
            /** @phpstan-ignore-next-line */
            protocol: $data['protocol'],
            /** @phpstan-ignore-next-line */
            port: $data['port'] ?? null,
            /** @phpstan-ignore-next-line */
            sourceIps: $data['source_ips'] ?? [],
            /** @phpstan-ignore-next-line */
            destinationIps: $data['destination_ips'] ?? [],
            /** @phpstan-ignore-next-line */
            description: $data['description'] ?? null,
        );
    }
}
