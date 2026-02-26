<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use PoorPlebs\HetznerCloudSdk\Models\Action;
use PoorPlebs\HetznerCloudSdk\Responses\ActionResponse;
use RuntimeException;

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

    /**
     * @param (callable(float): void)|null $sleep
     */
    public function poll(
        int $id,
        float $intervalSeconds = 1.0,
        int $maxAttempts = 120,
        ?callable $sleep = null,
    ): PromiseInterface {
        $sleep ??= static fn (float $seconds): null => usleep((int)($seconds * 1_000_000));

        return $this->pollAttempt($id, $intervalSeconds, $maxAttempts, 1, $sleep);
    }

    /**
     * @param callable(float): void $sleep
     */
    private function pollAttempt(
        int $id,
        float $intervalSeconds,
        int $maxAttempts,
        int $attempt,
        callable $sleep,
    ): PromiseInterface {
        return $this->get($id)->then(function (ActionResponse $response) use ($id, $intervalSeconds, $maxAttempts, $attempt, $sleep): PromiseInterface|Action {
            $action = $response->result;

            if ($action->status === 'success' || $action->status === 'error') {
                return $action;
            }

            if ($attempt >= $maxAttempts) {
                throw new RuntimeException(
                    "Action {$id} did not complete within {$maxAttempts} attempts",
                );
            }

            $sleep($intervalSeconds);

            return $this->pollAttempt($id, $intervalSeconds, $maxAttempts, $attempt + 1, $sleep);
        });
    }
}
