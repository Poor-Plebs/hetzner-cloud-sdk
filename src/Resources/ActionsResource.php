<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use PoorPlebs\HetznerCloudSdk\Responses\ActionResponse;

class ActionsResource
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function get(int $id): PromiseInterface
    {
        return $this->client
            ->getAsync("actions/{$id}")
            ->then(ActionResponse::make(...));
    }
}
