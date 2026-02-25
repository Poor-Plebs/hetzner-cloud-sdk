<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Responses;

use PoorPlebs\HetznerCloudSdk\Models\SshKey;

class SshKeyListResponse extends HetznerResponse
{
    /**
     * @var array<int,SshKey>
     */
    public readonly mixed $result; // @phpstan-ignore-line

    /**
     * @param array<string,mixed> $data
     */
    protected function init(array $data): void
    {
        /** @var array<int,array<string,mixed>> $sshKeys */
        $sshKeys = $data['ssh_keys'] ?? [];

        /** @phpstan-ignore-next-line */
        $this->result = array_map(
            static fn (array $sshKey): SshKey => SshKey::create($sshKey),
            $sshKeys,
        );
    }
}
