<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Pagination
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $previousPage,
        public readonly ?int $nextPage,
        public readonly int $lastPage,
        public readonly int $totalEntries,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            page: $data['page'],
            /** @phpstan-ignore-next-line */
            perPage: $data['per_page'],
            /** @phpstan-ignore-next-line */
            previousPage: $data['previous_page'] ?? null,
            /** @phpstan-ignore-next-line */
            nextPage: $data['next_page'] ?? null,
            /** @phpstan-ignore-next-line */
            lastPage: $data['last_page'],
            /** @phpstan-ignore-next-line */
            totalEntries: $data['total_entries'],
        );
    }
}
