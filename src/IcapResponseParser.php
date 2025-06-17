<?php
declare(strict_types=1);

namespace Ndrstmr\Icap;

use Ndrstmr\Icap\DTO\IcapResponse;
use Ndrstmr\Icap\Exception\IcapParseException;

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
        $protocol = [];
        $headers = [];
        $bodySections = [];
        $rawBody = '';

        foreach (preg_split('/\r?\n/', $response) as $line) {
            if ([] === $protocol) {
                if (0 !== strpos($line, IcapProtocolConstants::PROTOCOL_PREFIX)) {
                    throw new IcapParseException('Unknown ICAP response');
                }
                $parts = preg_split('/\ +/', $line, 3);
                $protocol = [
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
                $headers[$parts[0]] = $parts[1] ?? '';
            }
        }

        $body = preg_split('/\r?\n\r?\n/', $response, 2);
        if (isset($body[1])) {
            $rawBody = $body[1];
            if (array_key_exists(IcapProtocolConstants::HEADER_ENCAPSULATED, $headers)) {
                $encapsulated = [];
                $params = preg_split('/, /', $headers[IcapProtocolConstants::HEADER_ENCAPSULATED]);
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
                        case IcapBodySection::REQ_HDR->value:
                        case IcapBodySection::RES_HDR->value:
                            $bodySections[$section] = preg_split('/\r?\n\r?\n/', $data, 2)[0];
                            break;
                        case IcapBodySection::REQ_BODY->value:
                        case IcapBodySection::RES_BODY->value:
                            $parts = preg_split('/\r?\n/', $data, 2);
                            if (count($parts) === 2) {
                                $bodySections[$section] = substr($parts[1], 0, hexdec($parts[0]));
                            }
                            break;
                    }
                }
            }
        }

        return new IcapResponse($protocol, $headers, $bodySections, $rawBody);
    }
}
