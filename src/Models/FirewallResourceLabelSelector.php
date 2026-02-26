<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class FirewallResourceLabelSelector
{
    public function __construct(
        public readonly string $selector,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            selector: $data['selector'],
        );
    }
}
