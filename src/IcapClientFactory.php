<?php
declare(strict_types=1);

namespace Ndrstmr\Icap;

use Ndrstmr\Icap\Socket\PhpSocketClient;
use Ndrstmr\Icap\Socket\TlsSocketConnection;
use Ndrstmr\Icap\Transport\IcapTransport;

/**
 * Factory for creating ICAP clients with optional TLS support.
 */
final class IcapClientFactory
{
    public static function create(string $host, int $port, bool $tls = false): IcapClient
    {
        $connection = $tls ? new TlsSocketConnection() : new PhpSocketClient();
        $transport = new IcapTransport($host, $port, $connection);

        return new IcapClient($host, $port, $transport);
    }
}
