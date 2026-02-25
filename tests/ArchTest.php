<?php

declare(strict_types=1);

arch('all source files use strict types')
    ->expect('PoorPlebs\HetznerCloudSdk')
    ->toUseStrictTypes();

arch('all test files use strict types')
    ->expect('PoorPlebs\HetznerCloudSdk\Tests')
    ->toUseStrictTypes();

arch('no debugging functions in source code')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray'])
    ->not->toBeUsed();
