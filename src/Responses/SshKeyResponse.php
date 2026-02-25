<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Responses;

use PoorPlebs\HetznerCloudSdk\Models\SshKey;

class SshKeyResponse extends HetznerResponse
{
    /**
     * @var SshKey
     */
    public readonly mixed $result; // @phpstan-ignore-line

    /**
     * @param array<string,mixed> $data
     */
    protected function init(array $data): void
    {
        /** @phpstan-ignore-next-line */
        $this->result = SshKey::create($data['ssh_key']);
    }
}
