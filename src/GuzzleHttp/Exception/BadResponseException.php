<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk\GuzzleHttp\Exception;

use GuzzleHttp\Exception\BadResponseException as GuzzleBadResponseException;
use PoorPlebs\HetznerCloudSdk\Obfuscator\HetznerApiTokenObfuscator;
use Psr\Http\Message\RequestInterface;

class BadResponseException extends GuzzleBadResponseException
{
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
