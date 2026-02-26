<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Protection
{
    public function __construct(
        public readonly bool $delete,
        public readonly ?bool $rebuild,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            delete: $data['delete'],
            /** @phpstan-ignore-next-line */
            rebuild: $data['rebuild'] ?? null,
        );
    }
}
