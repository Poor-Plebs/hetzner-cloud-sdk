<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Obfuscator;

use PoorPlebs\GuzzleObfuscatedFormatter\Obfuscator\StringObfuscator;

class HetznerApiTokenObfuscator extends StringObfuscator
{
    public const TOKEN_REGEX = '/Bearer\s+\S+/';

    public function __invoke(mixed $value): string
    {
        return 'Bearer ' . parent::__invoke($value);
    }
}
