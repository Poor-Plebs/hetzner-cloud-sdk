<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class FirewallResourceServer
{
    public function __construct(
        public readonly int $id,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            id: $data['id'],
        );
    }
}
