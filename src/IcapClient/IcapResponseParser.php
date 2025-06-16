<?php
declare(strict_types=1);

namespace IcapClient;

use IcapClient\DTO\IcapResponse;
use IcapClient\Exception\IcapParseException;

/**
 * Parse raw ICAP responses into a structured representation.
 */
class IcapResponseParser
{
    /**
     * Parse the given response string.
     *
     * @throws IcapParseException If the response does not contain valid ICAP data
     */
    public function parse(string $response): IcapResponse
    {
        $result = new IcapResponse();

        foreach (preg_split('/\r?\n/', $response) as $line) {
            if ([] === $result->protocol) {
                if (0 !== strpos($line, IcapProtocolConstants::PROTOCOL_PREFIX)) {
                    throw new IcapParseException('Unknown ICAP response');
                }
                $parts = preg_split('/\ +/', $line, 3);
                $result->protocol = [
                    'icap' => $parts[0] ?? '',
                    'code' => $parts[1] ?? '',
                    'message' => $parts[2] ?? '',
                ];
                continue;
            }

            if ('' === $line) {
                break;
            }

            $parts = preg_split('/:\ /', $line, 2);
            if (isset($parts[0])) {
                $result->headers[$parts[0]] = $parts[1] ?? '';
            }
        }

        $body = preg_split('/\r?\n\r?\n/', $response, 2);
        if (isset($body[1])) {
            $result->rawBody = $body[1];
            if (array_key_exists(IcapProtocolConstants::HEADER_ENCAPSULATED, $result->headers)) {
                $encapsulated = [];
                $params = preg_split('/, /', $result->headers[IcapProtocolConstants::HEADER_ENCAPSULATED]);
                if (count($params) > 0) {
                    foreach ($params as $param) {
                        $parts = preg_split('/=/', $param);
                        if (count($parts) !== 2) {
                            continue;
                        }
                        $encapsulated[$parts[0]] = $parts[1];
                    }
                }
                foreach ($encapsulated as $section => $offset) {
                    $data = substr($body[1], (int)$offset);
                    switch ($section) {
                        case IcapProtocolConstants::SECTION_REQ_HDR:
                        case IcapProtocolConstants::SECTION_RES_HDR:
                            $result->body[$section] = preg_split('/\r?\n\r?\n/', $data, 2)[0];
                            break;
                        case IcapProtocolConstants::SECTION_REQ_BODY:
                        case IcapProtocolConstants::SECTION_RES_BODY:
                            $parts = preg_split('/\r?\n/', $data, 2);
                            if (count($parts) === 2) {
                                $result->body[$section] = substr($parts[1], 0, hexdec($parts[0]));
                            }
                            break;
                    }
                }
            }
        }

        return $result;
    }
}
