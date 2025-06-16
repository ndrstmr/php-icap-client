<?php
declare(strict_types=1);

namespace IcapClient;

use IcapClient\DTO\IcapRequest;
use IcapClient\Exception\IcapFileException;

/**
 * Converts {@link IcapRequest} objects into raw ICAP request strings.
 */
class IcapRequestFormatter
{
    /**
     * Create a raw request string from the provided request data.
     */
    public function format(IcapRequest $request): string
    {
        $headers = $request->headers;

        if (!array_key_exists(IcapProtocolConstants::HEADER_HOST, $headers)) {
            $headers[IcapProtocolConstants::HEADER_HOST] = $request->host;
        }
        if (!array_key_exists(IcapProtocolConstants::HEADER_USER_AGENT, $headers)) {
            $headers[IcapProtocolConstants::HEADER_USER_AGENT] = 'PHP-ICAP-CLIENT/0.5.0';
        }
        if (!array_key_exists(IcapProtocolConstants::HEADER_CONNECTION, $headers)) {
            $headers[IcapProtocolConstants::HEADER_CONNECTION] = 'close';
        }

        $bodyData = '';
        $hasBody = false;
        $encapsulated = [];
        foreach ($request->body as $type => $data) {
            switch ($type) {
                case IcapProtocolConstants::SECTION_REQ_HDR:
                case IcapProtocolConstants::SECTION_RES_HDR:
                    $encapsulated[$type] = strlen($bodyData);
                    if (is_resource($data)) {
                        $content = stream_get_contents($data);
                        if ($content === false) {
                            throw new Exception\IcapFileException('Unable to read body stream');
                        }
                    } else {
                        $content = $data;
                    }
                    $bodyData .= $content;
                    break;
                case IcapProtocolConstants::SECTION_REQ_BODY:
                case IcapProtocolConstants::SECTION_RES_BODY:
                    if (is_resource($data)) {
                        $content = stream_get_contents($data);
                        if ($content === false) {
                            throw new Exception\IcapFileException('Unable to read body stream');
                        }
                    } else {
                        $content = $data;
                    }
                    $encapsulated[$type] = strlen($bodyData);
                    $bodyData .= dechex(strlen($content)) . "\r\n";
                    $bodyData .= $content;
                    $bodyData .= "\r\n";
                    $hasBody = true;
                    break;
            }
        }

        if ($hasBody) {
            $bodyData .= "0\r\n\r\n";
        } elseif (count($encapsulated) > 0) {
            $encapsulated[IcapProtocolConstants::SECTION_NULL_BODY] = strlen($bodyData);
        }

        if (count($encapsulated) > 0) {
            $headers[IcapProtocolConstants::HEADER_ENCAPSULATED] = '';
            foreach ($encapsulated as $section => $offset) {
                $headers[IcapProtocolConstants::HEADER_ENCAPSULATED] .= $headers[IcapProtocolConstants::HEADER_ENCAPSULATED] === '' ? '' : ', ';
                $headers[IcapProtocolConstants::HEADER_ENCAPSULATED] .= "{$section}={$offset}";
            }
        }

        $result = "{$request->method} icap://{$request->host}/{$request->service} " .
            IcapProtocolConstants::PROTOCOL_PREFIX . IcapProtocolConstants::PROTOCOL_VERSION . "\r\n";
        foreach ($headers as $header => $value) {
            $sanitizedValue = str_replace(["\r", "\n"], '', $value);
            $result .= "{$header}: {$sanitizedValue}\r\n";
        }

        $result .= "\r\n";
        $result .= $bodyData;

        return $result;
    }
}
