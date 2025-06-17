<?php
declare(strict_types=1);

namespace Ndrstmr\Icap;

use Ndrstmr\Icap\DTO\IcapRequest;
use Ndrstmr\Icap\Exception\IcapFileException;

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
                case IcapBodySection::REQ_HDR->value:
                case IcapBodySection::RES_HDR->value:
                    $encapsulated[$type] = strlen($bodyData);
                    if (is_resource($data)) {
                        $content = stream_get_contents($data);
                        if ($content === false) {
                            throw new IcapFileException('Unable to read body stream');
                        }
                    } else {
                        $content = $data;
                    }
                    $bodyData .= $content;
                    break;
                case IcapBodySection::REQ_BODY->value:
                case IcapBodySection::RES_BODY->value:
                    if (is_resource($data)) {
                        $content = stream_get_contents($data);
                        if ($content === false) {
                            throw new IcapFileException('Unable to read body stream');
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
            $encapsulated[IcapBodySection::NULL_BODY->value] = strlen($bodyData);
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
            $sanitizedHeader = str_replace(["\r", "\n"], '', $header);
            $sanitizedValue = str_replace(["\r", "\n"], '', $value);
            $result .= "{$sanitizedHeader}: {$sanitizedValue}\r\n";
        }

        $result .= "\r\n";
        $result .= $bodyData;

        return $result;
    }

    /**
     * Create a generator producing the request in chunks. This avoids
     * buffering the entire body in memory. Body sections provided as
     * {@see \Generator} or iterable will be streamed.
     *
     * @return \Generator<string>
     */
    public function formatIterable(IcapRequest $request): \Generator
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

        $prefixData = '';
        $encapsulated = [];
        $streamSection = null;
        $streamData = null;

        foreach ($request->body as $type => $data) {
            switch ($type) {
                case IcapBodySection::REQ_HDR->value:
                case IcapBodySection::RES_HDR->value:
                    $encapsulated[$type] = strlen($prefixData);
                    if (is_resource($data)) {
                        $content = stream_get_contents($data);
                        if ($content === false) {
                            throw new IcapFileException('Unable to read body stream');
                        }
                    } else {
                        $content = $data;
                    }
                    $prefixData .= $content;
                    break;

                case IcapBodySection::REQ_BODY->value:
                case IcapBodySection::RES_BODY->value:
                    $encapsulated[$type] = strlen($prefixData);
                    $streamSection = $type;
                    if (is_iterable($data)) {
                        $streamData = $data;
                    } elseif (is_resource($data)) {
                        $streamData = (static function ($handle) {
                            while (!feof($handle)) {
                                $chunk = fread($handle, 8192);
                                if ($chunk === false) {
                                    break;
                                }
                                if ($chunk === '') {
                                    continue;
                                }
                                yield $chunk;
                            }
                        })($data);
                    } else {
                        $streamData = [$data];
                    }
                    break;
            }
        }

        if ($streamSection === null && count($encapsulated) > 0) {
            $encapsulated[IcapBodySection::NULL_BODY->value] = strlen($prefixData);
        }

        if (count($encapsulated) > 0) {
            $headers[IcapProtocolConstants::HEADER_ENCAPSULATED] = '';
            foreach ($encapsulated as $section => $offset) {
                $headers[IcapProtocolConstants::HEADER_ENCAPSULATED] .= $headers[IcapProtocolConstants::HEADER_ENCAPSULATED] === '' ? '' : ', ';
                $headers[IcapProtocolConstants::HEADER_ENCAPSULATED] .= "{$section}={$offset}";
            }
        }

        $requestLine = "{$request->method} icap://{$request->host}/{$request->service} " .
            IcapProtocolConstants::PROTOCOL_PREFIX . IcapProtocolConstants::PROTOCOL_VERSION . "\r\n";
        $headerString = '';
        foreach ($headers as $header => $value) {
            $sanitizedHeader = str_replace(["\r", "\n"], '', $header);
            $sanitizedValue = str_replace(["\r", "\n"], '', $value);
            $headerString .= "{$sanitizedHeader}: {$sanitizedValue}\r\n";
        }

        yield $requestLine . $headerString . "\r\n" . $prefixData;

        if ($streamSection !== null && $streamData !== null) {
            foreach ($streamData as $chunk) {
                if ($chunk === '') {
                    continue;
                }
                $len = dechex(strlen($chunk));
                yield $len . "\r\n" . $chunk . "\r\n";
            }
            yield "0\r\n\r\n";
        }
    }
}
