<?php
declare(strict_types=1);

namespace IcapClient\DTO;

class IcapResponse
{
    /** @var array<string,string> */
    public array $protocol;
    /** @var array<string,string> */
    public array $headers;
    /** @var array<string,string> */
    public array $body;
    public string $rawBody;

    public function __construct(array $protocol = [], array $headers = [], array $body = [], string $rawBody = '')
    {
        $this->protocol = $protocol;
        $this->headers = $headers;
        $this->body = $body;
        $this->rawBody = $rawBody;
    }
}
