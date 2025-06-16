<?php
declare(strict_types=1);

namespace IcapClient\Socket;

use Socket;

/**
 * Socket implementation using PHP's socket extension.
 */
class PhpSocketClient implements SocketClientInterface
{
    private ?Socket $socket = null;

    private float $readTimeout;

    private float $writeTimeout;

    public function __construct(float $readTimeout = 0.0, float $writeTimeout = 0.0)
    {
        $this->readTimeout = $readTimeout;
        $this->writeTimeout = $writeTimeout;
    }

    /**
     * {@inheritdoc}
     */
    public function connect(string $host, int $port): bool
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            return false;
        }

        socket_set_option(
            $this->socket,
            SOL_SOCKET,
            SO_RCVTIMEO,
            [
                'sec' => (int) $this->readTimeout,
                'usec' => (int) (($this->readTimeout - (int) $this->readTimeout) * 1_000_000),
            ]
        );
        socket_set_option(
            $this->socket,
            SOL_SOCKET,
            SO_SNDTIMEO,
            [
                'sec' => (int) $this->writeTimeout,
                'usec' => (int) (($this->writeTimeout - (int) $this->writeTimeout) * 1_000_000),
            ]
        );

        if (!socket_connect($this->socket, $host, $port)) {
            $this->disconnect();
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $data): int
    {
        if (!$this->socket instanceof Socket) {
            return 0;
        }

        return socket_write($this->socket, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function read(int $length): string
    {
        if (!$this->socket instanceof Socket) {
            return '';
        }

        return socket_read($this->socket, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        if ($this->socket instanceof Socket) {
            socket_shutdown($this->socket);
            socket_close($this->socket);
        }
        $this->socket = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastError(): int
    {
        return socket_last_error($this->socket);
    }

    public function setReadTimeout(float $timeout): void
    {
        $this->readTimeout = $timeout;
        if ($this->socket instanceof Socket) {
            socket_set_option(
                $this->socket,
                SOL_SOCKET,
                SO_RCVTIMEO,
                [
                    'sec' => (int) $timeout,
                    'usec' => (int) (($timeout - (int) $timeout) * 1_000_000),
                ]
            );
        }
    }

    public function getReadTimeout(): float
    {
        return $this->readTimeout;
    }

    public function setWriteTimeout(float $timeout): void
    {
        $this->writeTimeout = $timeout;
        if ($this->socket instanceof Socket) {
            socket_set_option(
                $this->socket,
                SOL_SOCKET,
                SO_SNDTIMEO,
                [
                    'sec' => (int) $timeout,
                    'usec' => (int) (($timeout - (int) $timeout) * 1_000_000),
                ]
            );
        }
    }

    public function getWriteTimeout(): float
    {
        return $this->writeTimeout;
    }

    public function waitForData(float $timeout): bool
    {
        if (!$this->socket instanceof Socket) {
            return false;
        }

        $read = [$this->socket];
        $write = null;
        $except = null;
        $sec = (int) $timeout;
        $usec = (int) (($timeout - $sec) * 1_000_000);
        $result = @socket_select($read, $write, $except, $sec, $usec);
        if ($result === false) {
            return false;
        }

        return $result > 0;
    }
}
