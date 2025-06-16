<?php
declare(strict_types=1);

namespace IcapClient\Transport;

interface TransportInterface
{
    /**
     * Send a complete request string and return the response.
     */
    public function send(string $request): string;

    /**
     * Send a request provided as iterable chunks and return the response.
     */
    public function sendIterable(iterable $request): string;

    /**
     * Close the underlying connection.
     */
    public function disconnect(): void;

    /**
     * Return last socket error code.
     */
    public function getLastSocketError(): int;

    /** Enable or disable persistent connections. */
    public function setPersistentConnection(bool $persistent): void;

    /** Check if persistent connections are enabled. */
    public function isPersistentConnection(): bool;

    /** Set the maximum response size in bytes. */
    public function setMaxResponseSize(int $maxSize): void;

    /** Get the maximum response size in bytes. */
    public function getMaxResponseSize(): int;

    /** Set read timeout in seconds. */
    public function setReadTimeout(float $timeout): void;

    /** Get read timeout in seconds. */
    public function getReadTimeout(): float;

    /** Set read buffer size in bytes. */
    public function setReadBufferSize(int $bufferSize): void;

    /** Get read buffer size in bytes. */
    public function getReadBufferSize(): int;
}
