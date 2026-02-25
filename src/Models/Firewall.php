<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Firewall
{
    /**
     * @param array<string,string> $labels
     * @param array<int,FirewallRule> $rules
     * @param array<int,FirewallResource> $appliedTo
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly array $labels,
        public readonly array $rules,
        public readonly array $appliedTo,
        public readonly string $created,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        /** @var array<int,array<string,mixed>> $rulesData */
        $rulesData = $data['rules'] ?? [];

        /** @var array<int,array<string,mixed>> $appliedToData */
        $appliedToData = $data['applied_to'] ?? [];

        return new self(
            /** @phpstan-ignore-next-line */
            id: $data['id'],
            /** @phpstan-ignore-next-line */
            name: $data['name'],
            /** @phpstan-ignore-next-line */
            labels: $data['labels'] ?? [],
            rules: array_map(
                static fn (array $rule): FirewallRule => FirewallRule::create($rule),
                $rulesData,
            ),
            appliedTo: array_map(
                static fn (array $resource): FirewallResource => FirewallResource::create($resource),
                $appliedToData,
            ),
            /** @phpstan-ignore-next-line */
            created: $data['created'],
        );
    }
}
