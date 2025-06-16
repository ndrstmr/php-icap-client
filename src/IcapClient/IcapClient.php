<?php
declare(strict_types=1);

namespace IcapClient;

use IcapClient\Socket\SocketClientInterface;
use IcapClient\Socket\PhpSocketClient;
use IcapClient\Exception\IcapClientException;
use IcapClient\Exception\IcapConnectionException;
use IcapClient\Exception\IcapResponseException;
use IcapClient\Exception\IcapFileException;
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

    /** @var int Port number */
    private int $port;

    /** @var SocketClientInterface Socket client implementation */
    private SocketClientInterface $socketClient;

    /** @var IcapRequestFormatter */
    private IcapRequestFormatter $requestFormatter;

    /** @var IcapResponseParser */
    private IcapResponseParser $responseParser;

    /** @var string User agent string */
    private string $userAgent = 'PHP-ICAP-CLIENT/0.5.0';

    /** @var bool Keep the socket connection open between requests */
    private bool $persistentConnection = false;

    /** @var bool Connection state */
    private bool $connected = false;

    /**
     * Constructor
     *
     * @param string $host IP address of ICAP server
     * @param int $port Port number
     */
    public function __construct(
        string $host,
        int $port,
        SocketClientInterface $socketClient = null,
        IcapRequestFormatter $requestFormatter = null,
        IcapResponseParser $responseParser = null
    )
    {
        $this->host = $host;
        $this->port = $port;
        $this->socketClient = $socketClient ?? new PhpSocketClient();
        $this->requestFormatter = $requestFormatter ?? new IcapRequestFormatter();
        $this->responseParser = $responseParser ?? new IcapResponseParser();
    }

    /**
     * Establish a socket connection to the configured ICAP server.
     *
     * The socket is closed and reset if the connection attempt fails and a
     * {@see IcapConnectionException} is thrown.
     *
     * @return bool True on success
     * @throws IcapConnectionException If the socket cannot be created or the
     *     connection fails
     */
    private function connect(): bool
    {
        if ($this->connected) {
            return true;
        }

        if (!$this->socketClient->connect($this->host, $this->port)) {
            $errorMessage = socket_strerror($this->socketClient->getLastError());
            throw new IcapConnectionException(
                "Cannot connect to icap://{$this->host}:{$this->port} (Socket error: {$errorMessage})"
            );
        }

        $this->connected = true;
        return true;
    }

    /**
     * Close connection to ICAP server
     */
    public function disconnect(): void
    {
        $this->socketClient->disconnect();
        $this->connected = false;
    }

    /**
     * Get last error code from socket object
     *
     * @return int Socket error code
     */
    public function getLastSocketError(): int
    {
        return $this->socketClient->getLastError();
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
        $this->persistentConnection = $persistent;
    }

    /**
     * Check if persistent connections are enabled.
     */
    public function isPersistentConnection(): bool
    {
        return $this->persistentConnection;
    }

    /**
     * Read the contents of a file and throw an exception on failure.
     *
     * @throws IcapFileException
     */
    protected function readFile(string $filename): string
    {
        $data = @file_get_contents($filename);
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
     * Send request
     *
     * @param string $request Request string
     * @return string Response string
     * @throws IcapClientException
     */
    public function send(string $request): string
    {
        // connect() now throws a specific connection exception with more detail
        $this->connect();

        $this->socketClient->write($request);

        $response = '';
        while ($buffer = $this->socketClient->read(2048)) {
            $response .= $buffer;
        }

        if (!$this->persistentConnection) {
            $this->disconnect();
        }
        return $response;
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