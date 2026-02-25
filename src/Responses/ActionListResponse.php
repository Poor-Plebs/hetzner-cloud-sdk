<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Responses;

use PoorPlebs\HetznerCloudSdk\Models\Action;

class ActionListResponse extends HetznerResponse
{
    /**
     * @var array<int,Action>
     */
    public readonly mixed $result; // @phpstan-ignore-line

    /**
     * @param array<string,mixed> $data
     */
    protected function init(array $data): void
    {
        /** @var array<int,array<string,mixed>> $actions */
        $actions = $data['actions'] ?? [];

        /** @phpstan-ignore-next-line */
        $this->result = array_map(
            static fn (array $action): Action => Action::create($action),
            $actions,
        );
    }
}
