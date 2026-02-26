<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Responses;

use PoorPlebs\HetznerCloudSdk\Models\Network;

class NetworkListResponse extends HetznerResponse
{
    /**
     * @var array<int,Network>
     */
    public readonly mixed $result; // @phpstan-ignore-line

    /**
     * @param array<string,mixed> $data
     */
    protected function init(array $data): void
    {
        /** @var array<int,array<string,mixed>> $networks */
        $networks = $data['networks'] ?? [];

        /** @phpstan-ignore-next-line */
        $this->result = array_map(
            static fn (array $network): Network => Network::create($network),
            $networks,
        );
    }
}
