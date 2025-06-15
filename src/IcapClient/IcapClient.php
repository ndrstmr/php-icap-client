<?php
declare(strict_types=1); // Added strict types 

namespace IcapClient;

use RuntimeException;
use Socket;

class IcapClient
{
    // Constants for ICAP methods and headers for better readability and maintainability
    private const ICAP_METHOD_OPTIONS = 'OPTIONS';
    private const ICAP_METHOD_RESPMOD = 'RESPMOD';
    private const ICAP_METHOD_REQMOD = 'REQMOD';

    private const HEADER_HOST = 'Host';
    private const HEADER_USER_AGENT = 'User-Agent';
    private const HEADER_CONNECTION = 'Connection';
    private const HEADER_ENCAPSULATED = 'Encapsulated';

    /** @var string Address of ICAP server */
    private string $host; // Added type hint 

    /** @var int Port number */
    private int $port; // Added type hint 

    /** @var ?Socket Socket object */
    private ?Socket $socket = null; // Added type hint and default null value 

    /** @var string User agent string */
    private string $userAgent = 'PHP-ICAP-CLIENT/0.5.0'; // Changed to private for better encapsulation 

    /**
     * Constructor
     *
     * @param string $host IP address of ICAP server
     * @param int $port Port number
     */
    public function __construct(string $host, int $port) // Added type hints
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Establish a socket connection to the configured ICAP server.
     *
     * The socket is closed and reset if the connection attempt fails and a
     * {@see RuntimeException} is thrown.
     *
     * @return bool True on success
     * @throws RuntimeException If the socket cannot be created or the
     *     connection fails
     */
    private function connect(): bool
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) { // Improved error check for socket_create 
            throw new RuntimeException('Failed to create socket.');
        }

        if (!socket_connect($this->socket, $this->host, $this->port)) {
            // Get detailed error message for better diagnostics
            $errorMessage = socket_strerror(socket_last_error($this->socket));
            socket_close($this->socket);
            $this->socket = null;
            throw new RuntimeException(
                "Cannot connect to icap://{$this->host}:{$this->port} (Socket error: {$errorMessage})"
            );
        }

        return true;
    }

    /**
     * Close connection to ICAP server
     */
    private function disconnect(): void // Added return type hint
    {
        if ($this->socket instanceof Socket) { // Check if socket is valid before shutdown/close 
            socket_shutdown($this->socket);
            socket_close($this->socket);
        }
        $this->socket = null; // Reset socket property 
    }

    /**
     * Get last error code from socket object
     *
     * @return int Socket error code
     */
    public function getLastSocketError(): int // Added return type hint
    {
        return socket_last_error($this->socket);
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
    public function getRequest(string $method, string $service, array $body = [], array $headers = []): string // Added type hints
    {
        if (!array_key_exists(self::HEADER_HOST, $headers)) { // Using constant 
            $headers[self::HEADER_HOST] = $this->host;
        }

        if (!array_key_exists(self::HEADER_USER_AGENT, $headers)) { // Using constant 
            $headers[self::HEADER_USER_AGENT] = $this->userAgent;
        }

        if (!array_key_exists(self::HEADER_CONNECTION, $headers)) { // Using constant 
            $headers[self::HEADER_CONNECTION] = 'close';
        }

        $bodyData = '';
        $hasBody = false;
        $encapsulated = [];
        foreach ($body as $type => $data) {
            switch ($type) {
                case 'req-hdr':
                case 'res-hdr':
                    $encapsulated[$type] = strlen($bodyData);
                    $bodyData .= $data;
                    break;

                case 'req-body':
                case 'res-body':
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
            $encapsulated['null-body'] = strlen($bodyData);
        }

        if (count($encapsulated) > 0) {
            $headers[self::HEADER_ENCAPSULATED] = ''; // Using constant 
            foreach ($encapsulated as $section => $offset) {
                $headers[self::HEADER_ENCAPSULATED] .= $headers[self::HEADER_ENCAPSULATED] === '' ?
                    '' : ', ';
                $headers[self::HEADER_ENCAPSULATED] .= "{$section}={$offset}";
            }
        }

        $request = "{$method} icap://{$this->host}/{$service} ICAP/1.0\r\n";
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
     * @throws RuntimeException
     */
    public function options(string $service): array // Added type hints
    {
        $request = $this->getRequest(self::ICAP_METHOD_OPTIONS, $service); // Using constant
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
     * @throws RuntimeException
     */
    public function respmod(string $service, array $body = [], array $headers = []): array // Added type hints
    {
        $request = $this->getRequest(self::ICAP_METHOD_RESPMOD, $service, $body, $headers); // Using constant
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
     * @throws RuntimeException
     */
    public function reqmod(string $service, array $body = [], array $headers = []): array // Added type hints
    {
        $request = $this->getRequest(self::ICAP_METHOD_REQMOD, $service, $body, $headers); // Using constant
        $response = $this->send($request);

        return $this->parseResponse($response);
    }

    /**
     * Send request
     *
     * @param string $request Request string
     * @return string Response string
     * @throws RuntimeException
     */
    public function send(string $request): string // Added type hint
    {
        // connect() now throws RuntimeException with more detail 
        $this->connect();

        socket_write($this->socket, $request);

        $response = '';
        while ($buffer = socket_read($this->socket, 2048)) {
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
     * @throws RuntimeException
     */
    private function parseResponse(string $response): array // Added type hint
    {
        $responseArray = [
            'protocol' => [],
            'headers' => [],
            'body' => [],
            'rawBody' => ''
        ];
        foreach (preg_split('/\r?\n/', $response) as $line) {
            if ([] === $responseArray['protocol']) {
                if (0 !== strpos($line, 'ICAP/')) {
                    throw new RuntimeException('Unknown ICAP response');
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
            if (array_key_exists(self::HEADER_ENCAPSULATED, $responseArray['headers'])) { // Using constant 
                $encapsulated = [];
                $params = preg_split('/, /', $responseArray['headers'][self::HEADER_ENCAPSULATED]); // Using constant 

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
                    $data = substr($body[1], (int)$offset); // Cast offset to int
                    switch ($section) {
                        case 'req-hdr':
                        case 'res-hdr':
                            $responseArray['body'][$section] = preg_split('/\r?\n\r?\n/', $data, 2)[0];
                            break;

                        case 'req-body':
                        case 'res-body':
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