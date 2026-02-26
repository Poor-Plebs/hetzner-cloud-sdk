<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Ipv6
{
    /**
     * @param array<int,array<string,string>>|null $dnsPtr
     */
    public function __construct(
        public readonly string $ip,
        public readonly bool $blocked,
        public readonly ?array $dnsPtr,
        public readonly ?int $id,
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
            /** @phpstan-ignore-next-line */
            dnsPtr: $data['dns_ptr'] ?? null,
            /** @phpstan-ignore-next-line */
            id: $data['id'] ?? null,
        );
    }
}
