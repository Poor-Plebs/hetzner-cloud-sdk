<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class SshKey
{
    /**
     * @param array<string,string> $labels
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $publicKey,
        public readonly string $fingerprint,
        public readonly array $labels,
        public readonly string $created,
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
            publicKey: $data['public_key'],
            /** @phpstan-ignore-next-line */
            fingerprint: $data['fingerprint'],
            /** @phpstan-ignore-next-line */
            labels: $data['labels'] ?? [],
            /** @phpstan-ignore-next-line */
            created: $data['created'],
        );
    }
}
