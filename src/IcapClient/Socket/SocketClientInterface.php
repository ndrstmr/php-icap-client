<?php
declare(strict_types=1);

namespace IcapClient\Socket;

interface SocketClientInterface
{
    public function connect(string $host, int $port): bool;

    public function write(string $data): int;

    public function read(int $length): string;

    public function disconnect(): void;

    public function getLastError(): int;
}
