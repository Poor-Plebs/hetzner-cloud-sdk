<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\Responses;

use PoorPlebs\HetznerCloudSdk\Models\Pagination;
use Psr\Http\Message\ResponseInterface;

class HetznerResponse
{
    public readonly ?Pagination $pagination;

    public readonly mixed $result;

    public function __construct(public readonly ResponseInterface $response)
    {
        /** @var array<string,mixed> $data */
        $data = json_decode(
            json: (string)$response->getBody(),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (array_key_exists('meta', $data) && is_array($data['meta']) && array_key_exists('pagination', $data['meta']) && is_array($data['meta']['pagination'])) {
            /** @var array<string,mixed> $paginationData */
            $paginationData = $data['meta']['pagination'];
            $this->pagination = Pagination::create($paginationData);
        } else {
            $this->pagination = null;
        }

        $this->init($data);
    }

    /**
     * @param array<string,mixed> $data
     */
    protected function init(array $data): void
    {
        /** @phpstan-ignore-next-line */
        $this->result = $data;
    }

    public static function make(ResponseInterface $response): static
    {
        /** @phpstan-ignore-next-line */
        return new static($response);
    }
}
