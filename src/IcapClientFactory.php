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
    public static function create(
        string $host,
        int $port,
        bool $tls = false,
        ?IcapClientConfig $config = null
    ): IcapClient
    {
        $connection = $tls ? new TlsSocketConnection() : new PhpSocketClient();
        $transport = new IcapTransport($host, $port, $connection);

        $config ??= new IcapClientConfig();
        $transport->setMaxResponseSize($config->maxResponseSize);
        $transport->setReadTimeout($config->readTimeout);
        $transport->setReadBufferSize($config->readBufferSize);
        $transport->setPersistentConnection($config->persistentConnection);

        $client = new IcapClient($host, $port, $transport);
        $client->setUserAgent($config->userAgent);

        return $client;
    }
}
