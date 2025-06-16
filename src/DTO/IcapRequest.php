<?php
declare(strict_types=1);

namespace Ndrstmr\Icap\DTO;

/**
 * Data transfer object describing an ICAP request.
 */
class IcapRequest
{
    public string $method;
    public string $host;
    public string $service;
    /** @var array<string,string> */
    public array $headers;
    /** @var array<string,string|resource> */
    public array $body;

    /**
     * @param string $method  ICAP method name
     * @param string $host    Target host
     * @param string $service Service name
     * @param array<string,string> $headers Additional headers
     * @param array<string,string|resource> $body    Encapsulated body sections
     */
    public function __construct(string $method, string $host, string $service, array $headers = [], array $body = [])
    {
        $this->method = $method;
        $this->host = $host;
        $this->service = $service;
        $this->headers = $headers;
        $this->body = $body;
    }
}
