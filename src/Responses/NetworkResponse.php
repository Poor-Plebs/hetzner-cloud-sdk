<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Responses;

use PoorPlebs\HetznerCloudSdk\Models\Network;

class NetworkResponse extends HetznerResponse
{
    /**
     * @var Network
     */
    public readonly mixed $result; // @phpstan-ignore-line

    /**
     * @param array<string,mixed> $data
     */
    protected function init(array $data): void
    {
        /** @phpstan-ignore-next-line */
        $this->result = Network::create($data['network']);
    }
}
