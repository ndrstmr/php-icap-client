<?php
declare(strict_types=1);

namespace Ndrstmr\Icap\Transport;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ndrstmr\Icap\Exception\IcapClientException;

/**
 * Transport implementation using a PSR-18 HTTP client.
 *
 * The complete ICAP request is sent as the body of an HTTP request.
 */
class Psr18Transport implements TransportInterface
{
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private string $uri;

    private bool $persistentConnection = false;
    private int $maxResponseSize = 10485760; // 10 MiB
    private float $readTimeout = 5.0;
    private int $readBufferSize = 8192;

    public function __construct(
        string $host,
        int $port,
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->uri = "http://{$host}:{$port}/";
    }

    public function setPersistentConnection(bool $persistent): void
    {
        $this->persistentConnection = $persistent;
    }

    public function isPersistentConnection(): bool
    {
        return $this->persistentConnection;
    }

    public function setMaxResponseSize(int $maxSize): void
    {
        $this->maxResponseSize = $maxSize;
    }

    public function getMaxResponseSize(): int
    {
        return $this->maxResponseSize;
    }

    public function setReadTimeout(float $timeout): void
    {
        $this->readTimeout = $timeout;
    }

    public function getReadTimeout(): float
    {
        return $this->readTimeout;
    }

    public function setReadBufferSize(int $bufferSize): void
    {
        $this->readBufferSize = $bufferSize;
    }

    public function getReadBufferSize(): int
    {
        return $this->readBufferSize;
    }

    public function getLastSocketError(): int
    {
        return 0;
    }

    public function disconnect(): void
    {
        // no persistent connection handling required
    }

    public function send(string $request): string
    {
        $stream = $this->streamFactory->createStream($request);
        $httpRequest = $this->requestFactory->createRequest('POST', $this->uri)
            ->withBody($stream);

        try {
            $response = $this->client->sendRequest($httpRequest);
        } catch (\Throwable $e) {
            throw new IcapClientException('HTTP client error: ' . $e->getMessage(), 0, $e);
        }

        $body = (string) $response->getBody();
        if (strlen($body) > $this->maxResponseSize) {
            throw new IcapClientException('Maximum response size exceeded');
        }

        return $body;
    }

    public function sendIterable(iterable $request): string
    {
        $data = '';
        foreach ($request as $chunk) {
            if ($chunk === '') {
                continue;
            }
            $data .= $chunk;
            if (strlen($data) > $this->maxResponseSize) {
                throw new IcapClientException('Maximum response size exceeded');
            }
        }

        return $this->send($data);
    }
}
