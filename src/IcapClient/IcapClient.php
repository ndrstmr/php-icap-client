<?php
declare(strict_types=1);

namespace IcapClient;

use IcapClient\Exception\IcapClientException;
use IcapClient\Exception\IcapResponseException;
use IcapClient\Exception\IcapFileException;
use IcapClient\Transport\TransportInterface;
use IcapClient\Transport\IcapTransport;
use IcapClient\IcapProtocolConstants;
use IcapClient\DTO\IcapRequest;
use IcapClient\DTO\IcapResponse;
use IcapClient\IcapRequestFormatter;
use IcapClient\IcapResponseParser;

/**
 * High level ICAP client handling request formatting and socket communication.
 */
class IcapClient
{

    /** @var string Address of ICAP server */
    private string $host;

    /** @var TransportInterface Transport implementation */
    private TransportInterface $transport;

    /** @var IcapRequestFormatter */
    private IcapRequestFormatter $requestFormatter;

    /** @var IcapResponseParser */
    private IcapResponseParser $responseParser;

    /** @var string User agent string */
    private string $userAgent = 'PHP-ICAP-CLIENT/0.5.0';


    /**
     * Constructor
     *
     * @param string $host IP address of ICAP server
     * @param int $port Port number
     */
    public function __construct(
        string $host,
        int $port,
        TransportInterface $transport = null,
        IcapRequestFormatter $requestFormatter = null,
        IcapResponseParser $responseParser = null
    )
    {
        $this->host = $host;
        $this->transport = $transport ?? new IcapTransport($host, $port, new \IcapClient\Socket\PhpSocketClient());
        $this->requestFormatter = $requestFormatter ?? new IcapRequestFormatter();
        $this->responseParser = $responseParser ?? new IcapResponseParser();
    }
    /**
     * Close connection to ICAP server
     */
    public function disconnect(): void
    {
        $this->transport->disconnect();
    }

    /**
     * Get last error code from socket object
     *
     * @return int Socket error code
     */
    public function getLastSocketError(): int
    {
        return $this->transport->getLastSocketError();
    }

    /**
     * Get the user agent string.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Set the user agent string.
     *
     * @param string $userAgent
     * @return void
     */
    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * Enable or disable persistent connections.
     */
    public function setPersistentConnection(bool $persistent): void
    {
        $this->transport->setPersistentConnection($persistent);
    }

    /**
     * Check if persistent connections are enabled.
     */
    public function isPersistentConnection(): bool
    {
        return $this->transport->isPersistentConnection();
    }

    /**
     * Set maximum allowed response size in bytes.
     */
    public function setMaxResponseSize(int $maxSize): void
    {
        $this->transport->setMaxResponseSize($maxSize);
    }

    /**
     * Get maximum allowed response size.
     */
    public function getMaxResponseSize(): int
    {
        return $this->transport->getMaxResponseSize();
    }

    /**
     * Set read timeout in seconds.
     */
    public function setReadTimeout(float $timeout): void
    {
        $this->transport->setReadTimeout($timeout);
    }

    /**
     * Get read timeout in seconds.
     */
    public function getReadTimeout(): float
    {
        return $this->transport->getReadTimeout();
    }

    /**
     * Set the read buffer size in bytes.
     */
    public function setReadBufferSize(int $bufferSize): void
    {
        $this->transport->setReadBufferSize($bufferSize);
    }

    /**
     * Get the read buffer size in bytes.
     */
    public function getReadBufferSize(): int
    {
        return $this->transport->getReadBufferSize();
    }

    /**
     * Read the contents of a file and throw an exception on failure.
     *
     * @throws IcapFileException
     */
    protected function readFile(string $filename): string
    {
        $data = file_get_contents($filename);
        if ($data === false) {
            throw new Exception\IcapFileException("Unable to read file: {$filename}");
        }

        return $data;
    }

    /**
     * Generate request string
     *
     * @param string $method ICAP method
     * @param string $service ICAP service
     * @param array $body Request body data
     * @param array $headers Array of headers
     * @return string Request string
     */
    public function getRequest(string $method, string $service, array $body = [], array $headers = []): string
    {
        $icapRequest = new IcapRequest($method, $this->host, $service, $headers, $body);

        return $this->requestFormatter->format($icapRequest);
    }

    /**
     * Generate request data as an iterable. Body sections provided as
     * {@see \Generator} or iterable will be streamed when sending.
     */
    public function getRequestIterable(string $method, string $service, array $body = [], array $headers = []): iterable
    {
        $icapRequest = new IcapRequest($method, $this->host, $service, $headers, $body);

        return $this->requestFormatter->formatIterable($icapRequest);
    }

    /**
     * Send OPTIONS request
     *
     * @param string $service ICAP service
     * @return array Response array
     * @throws IcapClientException
     */
    public function options(string $service): array
    {
        $request = $this->getRequest(IcapProtocolConstants::METHOD_OPTIONS, $service);
        $response = $this->send($request);

        return $this->parseResponse($response);
    }

    /**
     * Send RESPMOD request
     *
     * @param string $service ICAP service
     * @param array $body Request body data
     * @param array $headers Array of headers
     * @return array Response array
     * @throws IcapClientException
     */
    public function respmod(string $service, array $body = [], array $headers = []): array
    {
        $request = $this->getRequest(IcapProtocolConstants::METHOD_RESPMOD, $service, $body, $headers);
        $response = $this->send($request);

        return $this->parseResponse($response);
    }

    /**
     * Send RESPMOD request with streaming support.
     */
    public function respmodStream(string $service, array $body = [], array $headers = []): array
    {
        $request = $this->getRequestIterable(IcapProtocolConstants::METHOD_RESPMOD, $service, $body, $headers);
        $response = $this->sendIterable($request);

        return $this->parseResponse($response);
    }

    /**
     * Send REQMOD request
     *
     * @param string $service ICAP service
     * @param array $body Request body data
     * @param array $headers Array of headers
     * @return array Response array
     * @throws IcapClientException
     */
    public function reqmod(string $service, array $body = [], array $headers = []): array
    {
        $request = $this->getRequest(IcapProtocolConstants::METHOD_REQMOD, $service, $body, $headers);
        $response = $this->send($request);

        return $this->parseResponse($response);
    }

    /**
     * Send REQMOD request with streaming support.
     */
    public function reqmodStream(string $service, array $body = [], array $headers = []): array
    {
        $request = $this->getRequestIterable(IcapProtocolConstants::METHOD_REQMOD, $service, $body, $headers);
        $response = $this->sendIterable($request);

        return $this->parseResponse($response);
    }

    /**
     * Send request
     *
     * @param string $request Request string
     * @return string Response string
     * @throws IcapClientException
     */
    public function send(string $request): string
    {
        return $this->transport->send($request);
    }

    /**
     * Send a request provided as iterable chunks.
     *
     * @param iterable $request Iterable yielding string chunks
     * @return string Response string
     * @throws IcapClientException
     */
    public function sendIterable(iterable $request): string
    {
        return $this->transport->sendIterable($request);
    }

    /**
     * Parse response string
     *
     * @param string $response Response string
     * @return array Response array
     * @throws IcapResponseException
     */
    private function parseResponse(string $response): array
    {
        $parsed = $this->responseParser->parse($response);

        return [
            'protocol' => $parsed->protocol,
            'headers' => $parsed->headers,
            'body' => $parsed->body,
            'rawBody' => $parsed->rawBody,
        ];
    }
}