<?php
namespace Ndrstmr\Icap\Tests;

use Ndrstmr\Icap\IcapClient;
use Ndrstmr\Icap\Socket\SocketClientInterface;
use Ndrstmr\Icap\Transport\IcapTransport;
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
    public function waitForData(float $timeout): bool { return false; }
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
    public function waitForData(float $timeout): bool { return true; }
}

class TimeoutSocketClient implements SocketClientInterface
{
    private float $readTimeout = 0.0;
    public function connect(string $host, int $port): bool { return true; }
    public function write(string $data): int { return strlen($data); }
    public function read(int $length): string { return 'a'; }
    public function disconnect(): void {}
    public function getLastError(): int { return 0; }
    public function setReadTimeout(float $timeout): void { $this->readTimeout = $timeout; }
    public function getReadTimeout(): float { return $this->readTimeout; }
    public function setWriteTimeout(float $timeout): void {}
    public function getWriteTimeout(): float { return 0.0; }
    public function waitForData(float $timeout): bool {
        usleep((int)(($timeout + 0.05) * 1_000_000));
        return false;
    }
}

class IcapClientErrorHandlingTest extends TestCase
{
    public function testConnectionFailureThrowsException()
    {
        $transport = new IcapTransport('icap.test', 1344, new FailingSocketClient());
        $client = new IcapClient('icap.test', 1344, $transport);
        $this->expectException(\Ndrstmr\Icap\Exception\IcapConnectionException::class);
        $client->options('example');
    }

    public function testInvalidResponseThrowsException()
    {
        $socket = new InvalidResponseSocketClient(['BAD DATA', '']);
        $transport = new IcapTransport('icap.test', 1344, $socket);
        $client = new IcapClient('icap.test', 1344, $transport);
        $this->expectException(\Ndrstmr\Icap\Exception\IcapParseException::class);
        $client->options('example');
    }

    public function testReadTimeoutThrowsException()
    {
        $socket = new TimeoutSocketClient();
        $transport = new IcapTransport('icap.test', 1344, $socket);
        $transport->setReadTimeout(0.1);
        $client = new IcapClient('icap.test', 1344, $transport);
        $this->expectException(\Ndrstmr\Icap\Exception\IcapTimeoutException::class);
        $client->options('example');
    }
}
