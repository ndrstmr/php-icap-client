<?php
declare(strict_types=1);

namespace IcapClient;

use Socket;
use IcapClient\Socket\SocketClientInterface;
use IcapClient\Socket\PhpSocketClient;
use IcapClient\Exception\IcapClientException;
use IcapClient\Exception\IcapConnectionException;
use IcapClient\Exception\IcapResponseException;
use IcapClient\IcapProtocolConstants;

class IcapClient
{

    /** @var string Address of ICAP server */
    private string $host;

    /** @var int Port number */
    private int $port;

    /** @var SocketClientInterface Socket client implementation */
    private SocketClientInterface $socketClient;

    /** @var string User agent string */
    private string $userAgent = 'PHP-ICAP-CLIENT/0.5.0';

    /**
     * Constructor
     *
     * @param string $host IP address of ICAP server
     * @param int $port Port number
     */
    public function __construct(string $host, int $port, SocketClientInterface $socketClient = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->socketClient = $socketClient ?? new PhpSocketClient();
    }

    /**
     * Establish a socket connection to the configured ICAP server.
     *
     * The socket is closed and reset if the connection attempt fails and a
     * {@see IcapConnectionException} is thrown.
     *
     * @return bool True on success
     * @throws IcapConnectionException If the socket cannot be created or the
     *     connection fails
     */
    private function connect(): bool
    {
        if (!$this->socketClient->connect($this->host, $this->port)) {
            $errorMessage = socket_strerror($this->socketClient->getLastError());
            throw new IcapConnectionException(
                "Cannot connect to icap://{$this->host}:{$this->port} (Socket error: {$errorMessage})"
            );
        }

        return true;
    }

    /**
     * Close connection to ICAP server
     */
    private function disconnect(): void
    {
        $this->socketClient->disconnect();
    }

    /**
     * Get last error code from socket object
     *
     * @return int Socket error code
     */
    public function getLastSocketError(): int
    {
        return $this->socketClient->getLastError();
    }

    /**
     * Get the user agent string.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Set the user agent string.
     *
     * @param string $userAgent
     * @return void
     */
    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * Generate request string
     *
     * @param string $method ICAP method
     * @param string $service ICAP service
     * @param array $body Request body data
     * @param array $headers Array of headers
     * @return string Request string
     */
    public function getRequest(string $method, string $service, array $body = [], array $headers = []): string
    {
        if (!array_key_exists(IcapProtocolConstants::HEADER_HOST, $headers)) {
            $headers[IcapProtocolConstants::HEADER_HOST] = $this->host;
        }

        if (!array_key_exists(IcapProtocolConstants::HEADER_USER_AGENT, $headers)) {
            $headers[IcapProtocolConstants::HEADER_USER_AGENT] = $this->userAgent;
        }

        if (!array_key_exists(IcapProtocolConstants::HEADER_CONNECTION, $headers)) {
            $headers[IcapProtocolConstants::HEADER_CONNECTION] = 'close';
        }

        $bodyData = '';
        $hasBody = false;
        $encapsulated = [];
        foreach ($body as $type => $data) {
            switch ($type) {
                case IcapProtocolConstants::SECTION_REQ_HDR:
                case IcapProtocolConstants::SECTION_RES_HDR:
                    $encapsulated[$type] = strlen($bodyData);
                    $bodyData .= $data;
                    break;

                case IcapProtocolConstants::SECTION_REQ_BODY:
                case IcapProtocolConstants::SECTION_RES_BODY:
                    $encapsulated[$type] = strlen($bodyData);
                    $bodyData .= dechex(strlen($data)) . "\r\n";
                    $bodyData .= $data;
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

        $request = "{$method} icap://{$this->host}/{$service} " . IcapProtocolConstants::PROTOCOL_PREFIX . IcapProtocolConstants::PROTOCOL_VERSION . "\r\n";
        foreach ($headers as $header => $value) {
            $request .= "{$header}: {$value}\r\n";
        }

        $request .= "\r\n";
        $request .= $bodyData;

        return $request;
    }

    /**
     * Send OPTIONS request
     *
     * @param string $service ICAP service
     * @return array Response array
     * @throws IcapClientException
     */
    public function options(string $service): array
    {
        $request = $this->getRequest(IcapProtocolConstants::METHOD_OPTIONS, $service);
        $response = $this->send($request);

        return $this->parseResponse($response);
    }

    /**
     * Send RESPMOD request
     *
     * @param string $service ICAP service
     * @param array $body Request body data
     * @param array $headers Array of headers
     * @return array Response array
     * @throws IcapClientException
     */
    public function respmod(string $service, array $body = [], array $headers = []): array
    {
        $request = $this->getRequest(IcapProtocolConstants::METHOD_RESPMOD, $service, $body, $headers);
        $response = $this->send($request);

        return $this->parseResponse($response);
    }

    /**
     * Send REQMOD request
     *
     * @param string $service ICAP service
     * @param array $body Request body data
     * @param array $headers Array of headers
     * @return array Response array
     * @throws IcapClientException
     */
    public function reqmod(string $service, array $body = [], array $headers = []): array
    {
        $request = $this->getRequest(IcapProtocolConstants::METHOD_REQMOD, $service, $body, $headers);
        $response = $this->send($request);

        return $this->parseResponse($response);
    }

    /**
     * Send request
     *
     * @param string $request Request string
     * @return string Response string
     * @throws IcapClientException
     */
    public function send(string $request): string
    {
        // connect() now throws a specific connection exception with more detail
        $this->connect();

        $this->socketClient->write($request);

        $response = '';
        while ($buffer = $this->socketClient->read(2048)) {
            $response .= $buffer;
        }

        $this->disconnect();
        return $response;
    }

    /**
     * Parse response string
     *
     * @param string $response Response string
     * @return array Response array
     * @throws IcapResponseException
     */
    private function parseResponse(string $response): array
    {
        $responseArray = [
            'protocol' => [],
            'headers' => [],
            'body' => [],
            'rawBody' => ''
        ];
        foreach (preg_split('/\r?\n/', $response) as $line) {
            if ([] === $responseArray['protocol']) {
                if (0 !== strpos($line, IcapProtocolConstants::PROTOCOL_PREFIX)) {
                    throw new IcapResponseException('Unknown ICAP response');
                }

                $parts = preg_split('/\ +/', $line, 3);
                $responseArray['protocol'] = [
                    'icap' => isset($parts[0]) ?
                        $parts[0] : '',
                    'code' => isset($parts[1]) ?
                        $parts[1] : '',
                    'message' => isset($parts[2]) ?
                        $parts[2] : '',
                ];
                continue;
            }

            if ('' === $line) {
                break;
            }

            $parts = preg_split('/:\ /', $line, 2);
            if (isset($parts[0])) {
                $responseArray['headers'][$parts[0]] = isset($parts[1]) ?
                    $parts[1] : '';
            }
        }

        $body = preg_split('/\r?\n\r?\n/', $response, 2);
        if (isset($body[1])) {
            $responseArray['rawBody'] = $body[1];
            if (array_key_exists(IcapProtocolConstants::HEADER_ENCAPSULATED, $responseArray['headers'])) {
                $encapsulated = [];
                $params = preg_split('/, /', $responseArray['headers'][IcapProtocolConstants::HEADER_ENCAPSULATED]);

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
                            $responseArray['body'][$section] = preg_split('/\r?\n\r?\n/', $data, 2)[0];
                            break;

                        case IcapProtocolConstants::SECTION_REQ_BODY:
                        case IcapProtocolConstants::SECTION_RES_BODY:
                            $parts = preg_split('/\r?\n/', $data, 2);
                            if (count($parts) === 2) {
                                $responseArray['body'][$section] = substr($parts[1], 0, hexdec($parts[0]));
                            }
                            break;
                    }
                }
            }
        }

        return $responseArray;
    }
}