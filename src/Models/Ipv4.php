<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Ipv4
{
    public function __construct(
        public readonly string $ip,
        public readonly bool $blocked,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            ip: $data['ip'],
            /** @phpstan-ignore-next-line */
            blocked: $data['blocked'],
        );
    }
}
