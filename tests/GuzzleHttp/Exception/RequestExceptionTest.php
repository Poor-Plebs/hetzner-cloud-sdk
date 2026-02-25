<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PoorPlebs\HetznerCloudSdk\GuzzleHttp\Exception\BadResponseException;
use PoorPlebs\HetznerCloudSdk\GuzzleHttp\Exception\ClientException;
use PoorPlebs\HetznerCloudSdk\GuzzleHttp\Exception\RequestException;
use PoorPlebs\HetznerCloudSdk\GuzzleHttp\Exception\ServerException;

covers(RequestException::class, BadResponseException::class);

final class RequestExceptionTest extends BadResponseException
{
    public static function obfuscateHeaderPublic(Psr\Http\Message\RequestInterface $request): Psr\Http\Message\RequestInterface
    {
        return parent::obfuscateAuthorizationHeader($request);
    }
}

it('creates generic request exception when response is missing', function (): void {
    $request = new Request('GET', 'https://api.hetzner.cloud/v1/servers', [
        'Authorization' => 'Bearer my-secret-token',
    ]);

    $exception = RequestException::create($request);

    expect($exception)->toBeInstanceOf(RequestException::class)
        ->and($exception->getMessage())->toBe('Error completing request')
        ->and($exception->getRequest()->getHeaderLine('Authorization'))->toBe('Bearer **********');
});

it('creates client exception for 4xx responses', function (): void {
    $request = new Request('GET', 'https://api.hetzner.cloud/v1/servers', [
        'Authorization' => 'Bearer my-secret-token',
    ]);
    $response = new Response(404, [], 'Not Found');

    $exception = RequestException::create($request, $response);

    expect($exception)->toBeInstanceOf(ClientException::class)
        ->and($exception->getMessage())->toContain('Client error')
        ->and($exception->getRequest()->getHeaderLine('Authorization'))->toBe('Bearer **********');
});

it('creates server exception for 5xx responses', function (): void {
    $request = new Request('GET', 'https://api.hetzner.cloud/v1/servers', [
        'Authorization' => 'Bearer my-secret-token',
    ]);
    $response = new Response(500, [], 'Internal Server Error');

    $exception = RequestException::create($request, $response);

    expect($exception)->toBeInstanceOf(ServerException::class)
        ->and($exception->getMessage())->toContain('Server error');
});

it('creates unsuccessful request exception for non-4xx-5xx responses', function (): void {
    $request = new Request('GET', 'https://api.hetzner.cloud/v1/servers', [
        'Authorization' => 'Bearer my-secret-token',
    ]);
    $response = new Response(302, [], 'redirect');

    $exception = RequestException::create($request, $response);

    expect($exception)->toBeInstanceOf(RequestException::class)
        ->and($exception->getMessage())->toContain('Unsuccessful request');
});

it('obfuscates authorization header in bad response exception helper', function (): void {
    $request = new Request('GET', 'https://api.hetzner.cloud/v1/servers', [
        'Authorization' => 'Bearer my-secret-token',
    ]);

    $obfuscated = RequestExceptionTest::obfuscateHeaderPublic($request);

    expect($obfuscated->getHeaderLine('Authorization'))
        ->toBe('Bearer **********')
        ->not->toContain('my-secret-token');
});

it('leaves requests without authorization header unchanged', function (): void {
    $request = new Request('GET', 'https://api.hetzner.cloud/v1/servers');

    $obfuscated = RequestExceptionTest::obfuscateHeaderPublic($request);

    expect($obfuscated->hasHeader('Authorization'))->toBeFalse();
});
