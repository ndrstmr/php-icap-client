<?php
declare(strict_types=1);

namespace IcapClient\Transport;

use IcapClient\Exception\IcapClientException;
use IcapClient\Exception\IcapConnectionException;
use IcapClient\Socket\PhpSocketClient;
use IcapClient\Socket\SocketClientInterface;

/**
 * Default transport implementation using {@link SocketClientInterface}.
 */
class IcapTransport implements TransportInterface
{
    private string $host;
    private int $port;
    private SocketClientInterface $socketClient;

    private bool $persistentConnection = false;
    private bool $connected = false;
    private int $maxResponseSize = 10485760; // 10 MiB
    private float $readTimeout = 5.0;

    public function __construct(string $host, int $port, SocketClientInterface $socketClient = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->socketClient = $socketClient ?? new PhpSocketClient();
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
        $this->socketClient->setReadTimeout($timeout);
    }

    public function getReadTimeout(): float
    {
        return $this->readTimeout;
    }

    public function getLastSocketError(): int
    {
        return $this->socketClient->getLastError();
    }

    public function disconnect(): void
    {
        $this->socketClient->disconnect();
        $this->connected = false;
    }

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

    public function send(string $request): string
    {
        $this->connect();

        $this->socketClient->write($request);

        $response = $this->readResponse();

        if (!$this->persistentConnection) {
            $this->disconnect();
        }

        return $response;
    }

    public function sendIterable(iterable $request): string
    {
        $this->connect();

        foreach ($request as $chunk) {
            if ($chunk === '') {
                continue;
            }
            $this->socketClient->write($chunk);
        }

        $response = $this->readResponse();

        if (!$this->persistentConnection) {
            $this->disconnect();
        }

        return $response;
    }

    private function readResponse(): string
    {
        $response = '';
        $startTime = microtime(true);
        while (true) {
            $buffer = $this->socketClient->read(2048);

            if ($buffer === '') {
                if ($this->socketClient->getLastError() !== 0) {
                    $error = socket_strerror($this->socketClient->getLastError());
                    throw new IcapClientException("Socket read error: {$error}");
                }
                break;
            }

            $response .= $buffer;

            if (strlen($response) > $this->maxResponseSize) {
                throw new IcapClientException('Maximum response size exceeded');
            }

            if ((microtime(true) - $startTime) > $this->readTimeout) {
                throw new IcapClientException('Read timeout exceeded');
            }
        }

        return $response;
    }
}
