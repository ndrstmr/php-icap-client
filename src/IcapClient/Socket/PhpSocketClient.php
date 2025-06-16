<?php
declare(strict_types=1);

namespace IcapClient\Socket;

use Socket;

class PhpSocketClient implements SocketClientInterface
{
    private ?Socket $socket = null;

    public function connect(string $host, int $port): bool
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            return false;
        }

        if (!socket_connect($this->socket, $host, $port)) {
            $this->disconnect();
            return false;
        }

        return true;
    }

    public function write(string $data): int
    {
        if (!$this->socket instanceof Socket) {
            return 0;
        }

        return socket_write($this->socket, $data);
    }

    public function read(int $length): string
    {
        if (!$this->socket instanceof Socket) {
            return '';
        }

        return socket_read($this->socket, $length);
    }

    public function disconnect(): void
    {
        if ($this->socket instanceof Socket) {
            socket_shutdown($this->socket);
            socket_close($this->socket);
        }
        $this->socket = null;
    }

    public function getLastError(): int
    {
        return socket_last_error($this->socket);
    }
}
