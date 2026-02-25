<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Responses;

use PoorPlebs\HetznerCloudSdk\Models\Server;

class ServerResponse extends HetznerResponse
{
    /**
     * @var Server
     */
    public readonly mixed $result; // @phpstan-ignore-line

    /**
     * @param array<string,mixed> $data
     */
    protected function init(array $data): void
    {
        /** @phpstan-ignore-next-line */
        $this->result = Server::create($data['server']);
    }
}
