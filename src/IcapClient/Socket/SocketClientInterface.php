<?php
declare(strict_types=1);

namespace IcapClient\Socket;

/**
 * Basic socket client abstraction.
 */
interface SocketClientInterface
{
    /**
     * Open a connection to the given host and port.
     */
    public function connect(string $host, int $port): bool;

    /**
     * Write data to the socket.
     */
    public function write(string $data): int;

    /**
     * Read a chunk of data from the socket.
     */
    public function read(int $length): string;

    /**
     * Close the socket connection.
     */
    public function disconnect(): void;

    /**
     * Return the last socket error code.
     */
    public function getLastError(): int;

    /**
     * Set read timeout in seconds.
     */
    public function setReadTimeout(float $timeout): void;

    /**
     * Get read timeout in seconds.
     */
    public function getReadTimeout(): float;

    /**
     * Set write timeout in seconds.
     */
    public function setWriteTimeout(float $timeout): void;

    /**
     * Get write timeout in seconds.
     */
    public function getWriteTimeout(): float;
}
