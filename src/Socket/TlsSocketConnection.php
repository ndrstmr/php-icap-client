<?php
declare(strict_types=1);

namespace Ndrstmr\Icap\Socket;

/**
 * Socket implementation using TLS streams via stream_socket_client().
 */
class TlsSocketConnection implements SocketClientInterface, IcapConnectionInterface
{
    private $stream = null;
    private float $readTimeout;
    private float $writeTimeout;
    private int $lastError = 0;
    private bool $verifyPeer;
    private bool $verifyPeerName;

    public function __construct(float $readTimeout = 0.0, float $writeTimeout = 0.0, bool $verifyPeer = true, bool $verifyPeerName = true)
    {
        $this->readTimeout = $readTimeout;
        $this->writeTimeout = $writeTimeout;
        $this->verifyPeer = $verifyPeer;
        $this->verifyPeerName = $verifyPeerName;
    }

    public function connect(string $host, int $port): bool
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => $this->verifyPeer,
                'verify_peer_name' => $this->verifyPeerName,
                'allow_self_signed' => !$this->verifyPeer,
            ],
        ]);
        $uri = "tls://{$host}:{$port}";
        $this->stream = @stream_socket_client($uri, $errno, $errstr, $this->writeTimeout, STREAM_CLIENT_CONNECT, $context);
        if ($this->stream === false) {
            $this->lastError = $errno;
            $this->stream = null;
            return false;
        }
        stream_set_timeout($this->stream, (int)$this->readTimeout, (int)(($this->readTimeout - (int)$this->readTimeout) * 1_000_000));
        return true;
    }

    public function write(string $data): int
    {
        if (!is_resource($this->stream)) {
            return 0;
        }
        $result = @fwrite($this->stream, $data);
        if ($result === false) {
            $this->lastError = 1;
            return 0;
        }
        return $result;
    }

    public function read(int $length): string
    {
        if (!is_resource($this->stream)) {
            return '';
        }
        $data = @fread($this->stream, $length);
        if ($data === false) {
            $this->lastError = 1;
            return '';
        }
        return $data;
    }

    public function disconnect(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
    }

    public function getLastError(): int
    {
        return $this->lastError;
    }

    public function setReadTimeout(float $timeout): void
    {
        $this->readTimeout = $timeout;
        if (is_resource($this->stream)) {
            stream_set_timeout($this->stream, (int)$timeout, (int)(($timeout - (int)$timeout) * 1_000_000));
        }
    }

    public function getReadTimeout(): float
    {
        return $this->readTimeout;
    }

    public function setWriteTimeout(float $timeout): void
    {
        $this->writeTimeout = $timeout;
    }

    public function getWriteTimeout(): float
    {
        return $this->writeTimeout;
    }

    public function waitForData(float $timeout): bool
    {
        if (!is_resource($this->stream)) {
            return false;
        }
        $read = [$this->stream];
        $write = null;
        $except = null;
        $sec = (int)$timeout;
        $usec = (int)(($timeout - $sec) * 1_000_000);
        $result = @stream_select($read, $write, $except, $sec, $usec);
        if ($result === false) {
            return false;
        }
        return $result > 0;
    }
}
