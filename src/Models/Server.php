<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Server
{
    /**
     * @param array<string,string> $labels
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $status,
        public readonly PublicNet $publicNet,
        public readonly ServerType $serverType,
        public readonly Datacenter $datacenter,
        public readonly ?Image $image,
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
            status: $data['status'],
            /** @phpstan-ignore-next-line */
            publicNet: PublicNet::create($data['public_net']),
            /** @phpstan-ignore-next-line */
            serverType: ServerType::create($data['server_type']),
            /** @phpstan-ignore-next-line */
            datacenter: Datacenter::create($data['datacenter']),
            image: isset($data['image']) && is_array($data['image']) ? Image::create($data['image']) : null, /** @phpstan-ignore-line */
            /** @phpstan-ignore-next-line */
            labels: $data['labels'] ?? [],
            /** @phpstan-ignore-next-line */
            created: $data['created'],
        );
    }
}
