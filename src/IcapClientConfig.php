<?php
declare(strict_types=1);

namespace Ndrstmr\Icap;

/**
 * Configuration object for IcapClient instances.
 */
class IcapClientConfig
{
    public int $maxResponseSize;
    public string $userAgent;
    public float $readTimeout;
    public int $readBufferSize;
    public bool $persistentConnection;

    public function __construct(
        int $maxResponseSize = 10485760,
        string $userAgent = 'PHP-ICAP-CLIENT/0.5.0',
        float $readTimeout = 5.0,
        int $readBufferSize = 8192,
        bool $persistentConnection = false
    ) {
        $this->maxResponseSize = $maxResponseSize;
        $this->userAgent = $userAgent;
        $this->readTimeout = $readTimeout;
        $this->readBufferSize = $readBufferSize;
        $this->persistentConnection = $persistentConnection;
    }
}
