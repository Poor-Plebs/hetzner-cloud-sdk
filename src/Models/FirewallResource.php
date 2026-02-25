<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class FirewallResource
{
    /**
     * @param array<string,mixed>|null $server
     */
    public function __construct(
        public readonly string $type,
        public readonly ?array $server,
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
            server: $data['server'] ?? null,
        );
    }
}
