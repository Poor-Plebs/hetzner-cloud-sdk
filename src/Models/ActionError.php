<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Models;

class ActionError
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            code: $data['code'],
            /** @phpstan-ignore-next-line */
            message: $data['message'],
        );
    }
}
