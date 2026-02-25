<?php

declare(strict_types=1);

namespace PoorPlebs\HetznerCloudSdk;

use GuzzleHttp\BodySummarizerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use PoorPlebs\GuzzleConnectRetryDecider\ConnectRetryDecider;
use PoorPlebs\GuzzleObfuscatedFormatter\GuzzleHttp\ObfuscatedMessageFormatter;
use PoorPlebs\GuzzleRetryAfterMiddleware\RetryAfterMiddleware;
use PoorPlebs\HetznerCloudSdk\GuzzleHttp\Exception\RequestException;
use PoorPlebs\HetznerCloudSdk\Obfuscator\HetznerApiTokenObfuscator;
use PoorPlebs\HetznerCloudSdk\Psr\Log\WrappedLogger;
use PoorPlebs\HetznerCloudSdk\Resources\ActionsResource;
use PoorPlebs\HetznerCloudSdk\Resources\FirewallsResource;
use PoorPlebs\HetznerCloudSdk\Resources\ServersResource;
use PoorPlebs\HetznerCloudSdk\Resources\SshKeysResource;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\SimpleCache\CacheInterface;
use Throwable;

final class HetznerCloudClient implements LoggerAwareInterface
{
    public const BASE_URI = 'https://api.hetzner.cloud/v1/';

    public const DEFAULT_CONNECT_TIMEOUT = 2.0;

    public const DEFAULT_TIMEOUT = 30.0;

    private const RETRY_AFTER_CACHE_KEY_PREFIX = 'hetzner:retry-after:';

    protected Client $client;

    protected WrappedLogger $logger;

    private ?ActionsResource $actionsResource = null;

    private ?FirewallsResource $firewallsResource = null;

    private ?ServersResource $serversResource = null;

    private ?SshKeysResource $sshKeysResource = null;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(
        string $apiToken,
        CacheInterface $cache,
        array $config = [],
    ) {
        $this->logger = new WrappedLogger();

        $messageFormatter =
            (new ObfuscatedMessageFormatter(ObfuscatedMessageFormatter::DEBUG))
                ->setRequestHeaders([
                    HetznerApiTokenObfuscator::TOKEN_REGEX => new HetznerApiTokenObfuscator(),
                ]);

        $handlerStack = HandlerStack::create();

        $handlerStack->push(
            Middleware::retry(new ConnectRetryDecider()),
            'connect_retry',
        );

        $handlerStack->push(
            Middleware::log($this->logger, $messageFormatter, LogLevel::DEBUG),
            'obfuscated_logger',
        );

        $handlerStack->remove('http_errors');
        $handlerStack->unshift(self::httpErrors(), 'http_errors');

        $handlerStack->unshift(
            new RetryAfterMiddleware($cache),
            'retry_after',
        );

        $this->client = new Client(array_merge([
            'base_uri' => self::BASE_URI,
            'handler' => $handlerStack,
            RequestOptions::CONNECT_TIMEOUT => self::DEFAULT_CONNECT_TIMEOUT,
            RequestOptions::TIMEOUT => self::DEFAULT_TIMEOUT,
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $apiToken,
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
            ],
            RetryAfterMiddleware::REQUEST_OPTION => self::RETRY_AFTER_CACHE_KEY_PREFIX . hash('murmur3f', $apiToken),
        ], $config));
    }

    public function actions(): ActionsResource
    {
        if ($this->actionsResource === null) {
            $this->actionsResource = new ActionsResource($this->client);
        }

        return $this->actionsResource;
    }

    public function firewalls(): FirewallsResource
    {
        if ($this->firewallsResource === null) {
            $this->firewallsResource = new FirewallsResource($this->client);
        }

        return $this->firewallsResource;
    }

    public function servers(): ServersResource
    {
        if ($this->serversResource === null) {
            $this->serversResource = new ServersResource($this->client);
        }

        return $this->serversResource;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger->setLogger($logger);
    }

    public function sshKeys(): SshKeysResource
    {
        if ($this->sshKeysResource === null) {
            $this->sshKeysResource = new SshKeysResource($this->client);
        }

        return $this->sshKeysResource;
    }

    private static function httpErrors(
        ?BodySummarizerInterface $bodySummarizer = null
    ): callable {
        return static function (
            callable $handler
        ) use (
            $bodySummarizer
        ): callable {
            return static function (
                RequestInterface $request,
                array $options
            ) use (
                $handler,
                $bodySummarizer
            ): PromiseInterface {
                if (!array_key_exists('http_errors', $options) || $options['http_errors'] !== true) {
                    /** @var PromiseInterface $promise */
                    $promise = $handler($request, $options);

                    return $promise;
                }

                /** @var PromiseInterface $promise */
                $promise = $handler($request, $options);

                return $promise->then(
                    static function (ResponseInterface $response) use (
                        $request,
                        $bodySummarizer
                    ) {
                        $code = $response->getStatusCode();
                        if ($code < 400) {
                            return $response;
                        }

                        throw RequestException::create(
                            $request,
                            $response,
                            null,
                            [],
                            $bodySummarizer
                        );
                    },
                    static function (Throwable $exception) {
                        if ($exception instanceof ConnectException) {
                            $message = (string)preg_replace(
                                HetznerApiTokenObfuscator::TOKEN_REGEX,
                                'Bearer **********',
                                $exception->getMessage()
                            );

                            throw new ConnectException(
                                $message,
                                $exception->getRequest(),
                                null,
                                $exception->getHandlerContext()
                            );
                        }

                        throw $exception;
                    }
                );
            };
        };
    }
}
