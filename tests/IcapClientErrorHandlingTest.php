<?php

use IcapClient\IcapClient;
use IcapClient\Socket\SocketClientInterface;
use PHPUnit\Framework\TestCase;

class FailingSocketClient implements SocketClientInterface
{
    public function connect(string $host, int $port): bool { return false; }
    public function write(string $data): int { return 0; }
    public function read(int $length): string { return ''; }
    public function disconnect(): void {}
    public function getLastError(): int { return 111; }
    public function setReadTimeout(float $timeout): void {}
    public function getReadTimeout(): float { return 0.0; }
    public function setWriteTimeout(float $timeout): void {}
    public function getWriteTimeout(): float { return 0.0; }
}

class InvalidResponseSocketClient implements SocketClientInterface
{
    private array $responses;
    public function __construct(array $responses) { $this->responses = $responses; }
    public function connect(string $host, int $port): bool { return true; }
    public function write(string $data): int { return strlen($data); }
    public function read(int $length): string { return array_shift($this->responses) ?? ''; }
    public function disconnect(): void {}
    public function getLastError(): int { return 0; }
    public function setReadTimeout(float $timeout): void {}
    public function getReadTimeout(): float { return 0.0; }
    public function setWriteTimeout(float $timeout): void {}
    public function getWriteTimeout(): float { return 0.0; }
}

class IcapClientErrorHandlingTest extends TestCase
{
    public function testConnectionFailureThrowsException()
    {
        $client = new IcapClient('icap.test', 1344, new FailingSocketClient());
        $this->expectException(IcapClient\Exception\IcapConnectionException::class);
        $client->options('example');
    }

    public function testInvalidResponseThrowsException()
    {
        $socket = new InvalidResponseSocketClient(['BAD DATA', '']);
        $client = new IcapClient('icap.test', 1344, $socket);
        $this->expectException(IcapClient\Exception\IcapResponseException::class);
        $client->options('example');
    }
}
