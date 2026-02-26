<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class ServerType
{
    /**
     * @param array<int,array<string,mixed>> $prices
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly ?int $cores,
        public readonly ?float $memory,
        public readonly ?float $disk,
        public readonly ?string $cpuType,
        public readonly ?string $storageType,
        public readonly ?string $architecture,
        public readonly ?bool $deprecated,
        public readonly array $prices,
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
            name: $data['name'],
            /** @phpstan-ignore-next-line */
            description: $data['description'],
            /** @phpstan-ignore-next-line */
            cores: $data['cores'] ?? null,
            /** @phpstan-ignore-next-line */
            memory: isset($data['memory']) ? (float)$data['memory'] : null,
            /** @phpstan-ignore-next-line */
            disk: isset($data['disk']) ? (float)$data['disk'] : null,
            /** @phpstan-ignore-next-line */
            cpuType: $data['cpu_type'] ?? null,
            /** @phpstan-ignore-next-line */
            storageType: $data['storage_type'] ?? null,
            /** @phpstan-ignore-next-line */
            architecture: $data['architecture'] ?? null,
            /** @phpstan-ignore-next-line */
            deprecated: $data['deprecated'] ?? null,
            /** @phpstan-ignore-next-line */
            prices: $data['prices'] ?? [],
        );
    }
}
