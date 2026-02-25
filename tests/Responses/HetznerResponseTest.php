<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use PoorPlebs\HetznerCloudSdk\Responses\HetznerResponse;

covers(HetznerResponse::class);

it('parses json response body into result', function (): void {
    $response = new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode(['servers' => []], JSON_THROW_ON_ERROR),
    );

    $hetznerResponse = HetznerResponse::make($response);

    expect($hetznerResponse->result)->toBeArray()
        ->and($hetznerResponse->result)->toHaveKey('servers')
        ->and($hetznerResponse->pagination)->toBeNull();
});

it('extracts pagination metadata when present', function (): void {
    $response = new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode([
            'servers' => [],
            'meta' => [
                'pagination' => [
                    'page' => 2,
                    'per_page' => 25,
                    'previous_page' => 1,
                    'next_page' => 3,
                    'last_page' => 5,
                    'total_entries' => 120,
                ],
            ],
        ], JSON_THROW_ON_ERROR),
    );

    $hetznerResponse = HetznerResponse::make($response);

    expect($hetznerResponse->pagination)->not->toBeNull()
        ->and($hetznerResponse->pagination->page)->toBe(2)
        ->and($hetznerResponse->pagination->perPage)->toBe(25)
        ->and($hetznerResponse->pagination->previousPage)->toBe(1)
        ->and($hetznerResponse->pagination->nextPage)->toBe(3)
        ->and($hetznerResponse->pagination->lastPage)->toBe(5)
        ->and($hetznerResponse->pagination->totalEntries)->toBe(120);
});

it('sets pagination to null when meta is absent', function (): void {
    $response = new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode(['action' => ['id' => 1]], JSON_THROW_ON_ERROR),
    );

    $hetznerResponse = HetznerResponse::make($response);

    expect($hetznerResponse->pagination)->toBeNull();
});

it('preserves the original psr7 response', function (): void {
    $response = new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode(['servers' => []], JSON_THROW_ON_ERROR),
    );

    $hetznerResponse = HetznerResponse::make($response);

    expect($hetznerResponse->response)->toBe($response);
});
