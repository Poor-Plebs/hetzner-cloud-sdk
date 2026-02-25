<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Responses;

use PoorPlebs\HetznerCloudSdk\Models\Server;

class ServerListResponse extends HetznerResponse
{
    /**
     * @var array<int,Server>
     */
    public readonly mixed $result; // @phpstan-ignore-line

    /**
     * @param array<string,mixed> $data
     */
    protected function init(array $data): void
    {
        /** @var array<int,array<string,mixed>> $servers */
        $servers = $data['servers'] ?? [];

        /** @phpstan-ignore-next-line */
        $this->result = array_map(
            static fn (array $server): Server => Server::create($server),
            $servers,
        );
    }
}
