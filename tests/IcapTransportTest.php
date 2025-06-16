<?php

use IcapClient\Transport\IcapTransport;
use IcapClient\Socket\SocketClientInterface;
use IcapClient\Exception\IcapTimeoutException;
use PHPUnit\Framework\TestCase;

class StreamingSocketClient implements SocketClientInterface
{
    private array $chunks;
    private float $delay;
    private int $index = 0;

    public function __construct(array $chunks, float $delay)
    {
        $this->chunks = $chunks;
        $this->delay = $delay;
    }

    public function connect(string $host, int $port): bool { return true; }
    public function write(string $data): int { return strlen($data); }
    public function read(int $length): string { return $this->chunks[$this->index++] ?? ''; }
    public function disconnect(): void {}
    public function getLastError(): int { return 0; }
    public function setReadTimeout(float $timeout): void {}
    public function getReadTimeout(): float { return 0.0; }
    public function setWriteTimeout(float $timeout): void {}
    public function getWriteTimeout(): float { return 0.0; }
    public function waitForData(float $timeout): bool
    {
        if ($this->index >= count($this->chunks)) {
            usleep((int)($timeout * 1_000_000));
            return false;
        }
        usleep((int)($this->delay * 1_000_000));
        return true;
    }
}

class IcapTransportTest extends TestCase
{
    public function testLargeResponseArrivingInChunksDoesNotTimeout()
    {
        $chunks = [];
        for ($i = 0; $i < 20; $i++) {
            $chunks[] = str_repeat('a', 256);
        }
        $socket = new StreamingSocketClient($chunks, 0.05);
        $transport = new IcapTransport('icap.test', 1344, $socket);
        $transport->setReadTimeout(0.2);

        $response = $transport->send('REQ');

        $this->assertSame(implode('', $chunks), $response);
    }

    public function testWaitForDataTimeoutThrowsException()
    {
        $socket = new StreamingSocketClient([], 0.0);
        $transport = new IcapTransport('icap.test', 1344, $socket);
        $transport->setReadTimeout(0.1);

        $this->expectException(IcapTimeoutException::class);
        $transport->send('REQ');
    }
}
