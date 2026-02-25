<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class Action
{
    public function __construct(
        public readonly int $id,
        public readonly string $command,
        public readonly string $status,
        public readonly int $progress,
        public readonly string $started,
        public readonly ?string $finished,
        public readonly ?ActionError $error,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            id: $data['id'],
            /** @phpstan-ignore-next-line */
            command: $data['command'],
            /** @phpstan-ignore-next-line */
            status: $data['status'],
            /** @phpstan-ignore-next-line */
            progress: $data['progress'],
            /** @phpstan-ignore-next-line */
            started: $data['started'],
            /** @phpstan-ignore-next-line */
            finished: $data['finished'] ?? null,
            error: isset($data['error']) && is_array($data['error']) && isset($data['error']['code']) && $data['error']['code'] !== ''
                ? ActionError::create($data['error']) /** @phpstan-ignore-line */
                : null,
        );
    }
}
