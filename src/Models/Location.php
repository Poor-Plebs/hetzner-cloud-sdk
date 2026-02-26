<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Location
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $city,
        public readonly string $country,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly string $networkZone,
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
            city: $data['city'],
            /** @phpstan-ignore-next-line */
            country: $data['country'],
            /** @phpstan-ignore-next-line */
            latitude: (float)$data['latitude'],
            /** @phpstan-ignore-next-line */
            longitude: (float)$data['longitude'],
            /** @phpstan-ignore-next-line */
            networkZone: $data['network_zone'],
        );
    }
}
