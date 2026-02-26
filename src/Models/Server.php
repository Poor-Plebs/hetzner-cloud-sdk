<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Server
{
    /**
     * @param array<string,string> $labels
     * @param array<int,ServerPrivateNet> $privateNet
     * @param array<int,int> $volumes
     * @param array<int,int> $loadBalancers
     * @param array<string,mixed>|null $iso
     * @param array<string,mixed>|null $placementGroup
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $status,
        public readonly PublicNet $publicNet,
        public readonly ServerType $serverType,
        public readonly ?Datacenter $datacenter,
        public readonly ?Image $image,
        public readonly array $labels,
        public readonly string $created,
        public readonly ?Location $location,
        public readonly bool $locked,
        public readonly bool $rescueEnabled,
        public readonly ?Protection $protection,
        public readonly array $privateNet,
        public readonly array $volumes,
        public readonly array $loadBalancers,
        public readonly ?int $primaryDiskSize,
        public readonly ?int $includedTraffic,
        public readonly ?int $ingoingTraffic,
        public readonly ?int $outgoingTraffic,
        public readonly ?array $iso,
        public readonly ?string $backupWindow,
        public readonly ?array $placementGroup,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        /** @var array<int,array<string,mixed>> $privateNetData */
        $privateNetData = $data['private_net'] ?? [];

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
            datacenter: isset($data['datacenter']) && is_array($data['datacenter']) ? Datacenter::create($data['datacenter']) : null, /** @phpstan-ignore-line */
            image: isset($data['image']) && is_array($data['image']) ? Image::create($data['image']) : null, /** @phpstan-ignore-line */
            /** @phpstan-ignore-next-line */
            labels: $data['labels'] ?? [],
            /** @phpstan-ignore-next-line */
            created: $data['created'],
            location: isset($data['location']) && is_array($data['location']) ? Location::create($data['location']) : null, /** @phpstan-ignore-line */
            /** @phpstan-ignore-next-line */
            locked: $data['locked'] ?? false,
            /** @phpstan-ignore-next-line */
            rescueEnabled: $data['rescue_enabled'] ?? false,
            protection: isset($data['protection']) && is_array($data['protection']) ? Protection::create($data['protection']) : null, /** @phpstan-ignore-line */
            privateNet: array_map(
                static fn (array $net): ServerPrivateNet => ServerPrivateNet::create($net),
                $privateNetData,
            ),
            /** @phpstan-ignore-next-line */
            volumes: $data['volumes'] ?? [],
            /** @phpstan-ignore-next-line */
            loadBalancers: $data['load_balancers'] ?? [],
            /** @phpstan-ignore-next-line */
            primaryDiskSize: $data['primary_disk_size'] ?? null,
            /** @phpstan-ignore-next-line */
            includedTraffic: $data['included_traffic'] ?? null,
            /** @phpstan-ignore-next-line */
            ingoingTraffic: $data['ingoing_traffic'] ?? null,
            /** @phpstan-ignore-next-line */
            outgoingTraffic: $data['outgoing_traffic'] ?? null,
            /** @phpstan-ignore-next-line */
            iso: $data['iso'] ?? null,
            /** @phpstan-ignore-next-line */
            backupWindow: $data['backup_window'] ?? null,
            /** @phpstan-ignore-next-line */
            placementGroup: $data['placement_group'] ?? null,
        );
    }
}
