<?php
declare(strict_types=1);

namespace IcapClient\DTO;

class IcapRequest
{
    public string $method;
    public string $host;
    public string $service;
    /** @var array<string,string> */
    public array $headers;
    /** @var array<string,string> */
    public array $body;

    public function __construct(string $method, string $host, string $service, array $headers = [], array $body = [])
    {
        $this->method = $method;
        $this->host = $host;
        $this->service = $service;
        $this->headers = $headers;
        $this->body = $body;
    }
}
