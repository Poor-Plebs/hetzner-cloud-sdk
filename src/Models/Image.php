<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Image
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly string $type,
        public readonly ?string $description,
        public readonly string $status,
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
            /** @phpstan-ignore-next-line */
            name: $data['name'] ?? null,
            /** @phpstan-ignore-next-line */
            type: $data['type'],
            /** @phpstan-ignore-next-line */
            description: $data['description'] ?? null,
            /** @phpstan-ignore-next-line */
            status: $data['status'],
        );
    }
}
