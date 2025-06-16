<?php
declare(strict_types=1);

namespace Ndrstmr\Icap\DTO;

/**
 * Data transfer object describing an ICAP response.
 */
class IcapResponse
{
    /** @var array<string,string> */
    public array $protocol;
    /** @var array<string,string> */
    public array $headers;
    /** @var array<string,string> */
    public array $body;
    public string $rawBody;

    /**
     * @param array<string,string> $protocol Parsed protocol info
     * @param array<string,string> $headers  Response headers
     * @param array<string,string> $body     Encapsulated body sections
     * @param string $rawBody                Unparsed body data
     */
    public function __construct(array $protocol = [], array $headers = [], array $body = [], string $rawBody = '')
    {
        $this->protocol = $protocol;
        $this->headers = $headers;
        $this->body = $body;
        $this->rawBody = $rawBody;
    }
}
