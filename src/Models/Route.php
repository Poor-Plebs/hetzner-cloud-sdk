<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Route
{
    public function __construct(
        public readonly string $destination,
        public readonly string $gateway,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            destination: $data['destination'],
            /** @phpstan-ignore-next-line */
            gateway: $data['gateway'],
        );
    }
}
