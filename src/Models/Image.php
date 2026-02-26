<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Image
{
    /**
     * @param array<string,string> $labels
     * @param array<string,mixed>|null $createdFrom
     */
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly string $type,
        public readonly ?string $description,
        public readonly string $status,
        public readonly ?string $architecture,
        public readonly ?string $osFlavor,
        public readonly ?string $osVersion,
        public readonly ?float $diskSize,
        public readonly ?string $created,
        public readonly array $labels,
        public readonly ?bool $rapidDeploy,
        public readonly ?string $deprecated,
        public readonly ?float $imageSize,
        public readonly ?int $boundTo,
        public readonly ?array $createdFrom,
        public readonly ?string $deleted,
        public readonly ?Protection $protection,
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
            /** @phpstan-ignore-next-line */
            architecture: $data['architecture'] ?? null,
            /** @phpstan-ignore-next-line */
            osFlavor: $data['os_flavor'] ?? null,
            /** @phpstan-ignore-next-line */
            osVersion: $data['os_version'] ?? null,
            /** @phpstan-ignore-next-line */
            diskSize: isset($data['disk_size']) ? (float)$data['disk_size'] : null,
            /** @phpstan-ignore-next-line */
            created: $data['created'] ?? null,
            /** @phpstan-ignore-next-line */
            labels: $data['labels'] ?? [],
            /** @phpstan-ignore-next-line */
            rapidDeploy: $data['rapid_deploy'] ?? null,
            /** @phpstan-ignore-next-line */
            deprecated: $data['deprecated'] ?? null,
            /** @phpstan-ignore-next-line */
            imageSize: isset($data['image_size']) ? (float)$data['image_size'] : null,
            /** @phpstan-ignore-next-line */
            boundTo: $data['bound_to'] ?? null,
            /** @phpstan-ignore-next-line */
            createdFrom: $data['created_from'] ?? null,
            /** @phpstan-ignore-next-line */
            deleted: $data['deleted'] ?? null,
            protection: isset($data['protection']) && is_array($data['protection']) ? Protection::create($data['protection']) : null, /** @phpstan-ignore-line */
        );
    }
}
