<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\GuzzleHttp\Exception;

use GuzzleHttp\BodySummarizer;
use GuzzleHttp\BodySummarizerInterface;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use PoorPlebs\HetznerCloudSdk\Obfuscator\HetznerApiTokenObfuscator;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class RequestException extends GuzzleRequestException
{
    /**
     * @param array<mixed> $handlerContext
     */
    public static function create(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        ?Throwable $previous = null,
        array $handlerContext = [],
        ?BodySummarizerInterface $bodySummarizer = null
    ): GuzzleRequestException {
        if (!$response instanceof ResponseInterface) {
            return new self(
                'Error completing request',
                static::obfuscateAuthorizationHeader($request),
                null,
                $previous,
                $handlerContext
            );
        }

        $level = (int)floor($response->getStatusCode() / 100);
        if ($level === 4) {
            $label = 'Client error';
            $className = ClientException::class;
        } elseif ($level === 5) {
            $label = 'Server error';
            $className = ServerException::class;
        } else {
            $label = 'Unsuccessful request';
            $className = self::class;
        }

        $obfuscatedRequest = static::obfuscateAuthorizationHeader($request);

        $message = sprintf(
            '%s: `%s %s` resulted in a `%s %s` response',
            $label,
            $obfuscatedRequest->getMethod(),
            $obfuscatedRequest->getUri()->__toString(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        $summary = ($bodySummarizer ?? new BodySummarizer())->summarize($response);

        if ($summary !== null) {
            $message .= ":\n{$summary}\n";
        }

        return new $className($message, $obfuscatedRequest, $response, $previous, $handlerContext);
    }

    protected static function obfuscateAuthorizationHeader(RequestInterface $request): RequestInterface
    {
        if (!$request->hasHeader('Authorization')) {
            return $request;
        }

        $header = $request->getHeaderLine('Authorization');
        $obfuscated = (string)preg_replace(
            HetznerApiTokenObfuscator::TOKEN_REGEX,
            'Bearer **********',
            $header
        );

        return $request->withHeader('Authorization', $obfuscated);
    }
}
