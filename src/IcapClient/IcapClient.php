<?php
declare(strict_types=1); // Added strict types 

namespace IcapClient;

use RuntimeException;
use Socket; // Added use statement for Socket object 

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
    public function __construct(string $host, int $port) // Added type hints [cite: 1]
    {
        $this->host = $host; [cite: 1]
        $this->port = $port; [cite: 1]
    }

    /**
     * Connect to ICAP server
     *
     * @return bool True if successful
     * @throws RuntimeException
     */
    private function connect(): bool // Added return type hint [cite: 1]
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); [cite: 1]
        if ($this->socket === false) { // Improved error check for socket_create 
            throw new RuntimeException('Failed to create socket.');
        }

        if (!socket_connect($this->socket, $this->host, $this->port)) { [cite: 1]
            // Get detailed error message for better diagnostics
            $errorMessage = socket_strerror(socket_last_error($this->socket));
            throw new RuntimeException("Cannot connect to icap://{$this->host}:{$this->port} (Socket error: {$errorMessage})");
        }

        return true; [cite: 1]
    }

    /**
     * Close connection to ICAP server
     */
    private function disconnect(): void // Added return type hint [cite: 1]
    {
        if ($this->socket instanceof Socket) { // Check if socket is valid before shutdown/close 
            socket_shutdown($this->socket); [cite: 1]
            socket_close($this->socket); [cite: 1]
        }
        $this->socket = null; // Reset socket property 
    }

    /**
     * Get last error code from socket object
     *
     * @return int Socket error code
     */
    public function getLastSocketError(): int // Added return type hint [cite: 1]
    {
        return socket_last_error($this->socket); [cite: 1]
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
    public function getRequest(string $method, string $service, array $body = [], array $headers = []): string // Added type hints [cite: 1]
    {
        if (!array_key_exists(self::HEADER_HOST, $headers)) { // Using constant 
            $headers[self::HEADER_HOST] = $this->host; [cite: 1]
        }

        if (!array_key_exists(self::HEADER_USER_AGENT, $headers)) { // Using constant 
            $headers[self::HEADER_USER_AGENT] = $this->userAgent; [cite: 1]
        }

        if (!array_key_exists(self::HEADER_CONNECTION, $headers)) { // Using constant 
            $headers[self::HEADER_CONNECTION] = 'close'; [cite: 1]
        }

        $bodyData = '';
        $hasBody = false; [cite: 1]
        $encapsulated = []; [cite: 1]
        foreach ($body as $type => $data) { [cite: 1]
            switch ($type) { [cite: 1]
                case 'req-hdr': [cite: 1]
                case 'res-hdr': [cite: 1]
                    $encapsulated[$type] = strlen($bodyData); [cite: 1]
                    $bodyData .= $data; [cite: 1]
                    break;

                case 'req-body': [cite: 1]
                case 'res-body': [cite: 1]
                    $encapsulated[$type] = strlen($bodyData); [cite: 1]
                    $bodyData .= dechex(strlen($data)) . "\r\n"; [cite: 1]
                    $bodyData .= $data; [cite: 1]
                    $bodyData .= "\r\n"; [cite: 1]
                    $hasBody = true; [cite: 1]
                    break;
            }
        }

        if ($hasBody) { [cite: 1]
            $bodyData .= "0\r\n\r\n"; [cite: 1]
        } elseif (count($encapsulated) > 0) { [cite: 1]
            $encapsulated['null-body'] = strlen($bodyData); [cite: 1]
        }

        if (count($encapsulated) > 0) { [cite: 1]
            $headers[self::HEADER_ENCAPSULATED] = ''; // Using constant 
            foreach ($encapsulated as $section => $offset) { [cite: 1]
                $headers[self::HEADER_ENCAPSULATED] .= $headers[self::HEADER_ENCAPSULATED] === '' ?
                    '' : ', '; [cite: 1]
                $headers[self::HEADER_ENCAPSULATED] .= "{$section}={$offset}"; [cite: 1]
            }
        }

        $request = "{$method} icap://{$this->host}/{$service} ICAP/1.0\r\n"; [cite: 1]
        foreach ($headers as $header => $value) { [cite: 1]
            $request .= "{$header}: {$value}\r\n"; [cite: 1]
        }

        $request .= "\r\n"; [cite: 1]
        $request .= $bodyData; [cite: 1]

        return $request; [cite: 1]
    }

    /**
     * Send OPTIONS request
     *
     * @param string $service ICAP service
     * @return array Response array
     * @throws RuntimeException
     */
    public function options(string $service): array // Added type hints [cite: 1]
    {
        $request = $this->getRequest(self::ICAP_METHOD_OPTIONS, $service); // Using constant
        $response = $this->send($request); [cite: 1]

        return $this->parseResponse($response); [cite: 1]
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
    public function respmod(string $service, array $body = [], array $headers = []): array // Added type hints [cite: 1]
    {
        $request = $this->getRequest(self::ICAP_METHOD_RESPMOD, $service, $body, $headers); // Using constant
        $response = $this->send($request); [cite: 1]

        return $this->parseResponse($response); [cite: 1]
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
    public function reqmod(string $service, array $body = [], array $headers = []): array // Added type hints [cite: 1]
    {
        $request = $this->getRequest(self::ICAP_METHOD_REQMOD, $service, $body, $headers); // Using constant
        $response = $this->send($request); [cite: 1]

        return $this->parseResponse($response); [cite: 1]
    }

    /**
     * Send request
     *
     * @param string $request Request string
     * @return string Response string
     * @throws RuntimeException
     */
    public function send(string $request): string // Added type hint [cite: 1]
    {
        // connect() now throws RuntimeException with more detail 
        $this->connect(); [cite: 1]

        socket_write($this->socket, $request); [cite: 1]

        $response = ''; [cite: 1]
        while ($buffer = socket_read($this->socket, 2048)) { [cite: 1]
            $response .= $buffer; [cite: 1]
        }

        $this->disconnect(); [cite: 1]
        return $response; [cite: 1]
    }

    /**
     * Parse response string
     *
     * @param string $response Response string
     * @return array Response array
     * @throws RuntimeException
     */
    private function parseResponse(string $response): array // Added type hint [cite: 1]
    {
        $responseArray = [ [cite: 1]
            'protocol' => [], [cite: 1]
            'headers' => [], [cite: 1]
            'body' => [], [cite: 1]
            'rawBody' => '' [cite: 1]
        ];
        foreach (preg_split('/\r?\n/', $response) as $line) { [cite: 1]
            if ([] === $responseArray['protocol']) { [cite: 1]
                if (0 !== strpos($line, 'ICAP/')) { [cite: 1]
                    throw new RuntimeException('Unknown ICAP response'); [cite: 1]
                }

                $parts = preg_split('/\ +/', $line, 3); [cite: 1]
                $responseArray['protocol'] = [ [cite: 1]
                    'icap' => isset($parts[0]) ? [cite: 1]
                        $parts[0] : '', [cite: 1]
                    'code' => isset($parts[1]) ? [cite: 1]
                        $parts[1] : '', [cite: 1]
                    'message' => isset($parts[2]) ? [cite: 1]
                        $parts[2] : '', [cite: 1]
                ]; [cite: 1]
                continue; [cite: 1]
            }

            if ('' === $line) { [cite: 1]
                break; [cite: 1]
            }

            $parts = preg_split('/:\ /', $line, 2); [cite: 1]
            if (isset($parts[0])) { [cite: 1]
                $responseArray['headers'][$parts[0]] = isset($parts[1]) ? [cite: 1]
                    $parts[1] : ''; [cite: 1]
            }
        }

        $body = preg_split('/\r?\n\r?\n/', $response, 2); [cite: 1]
        if (isset($body[1])) { [cite: 1]
            $responseArray['rawBody'] = $body[1]; [cite: 1]
            if (array_key_exists(self::HEADER_ENCAPSULATED, $responseArray['headers'])) { // Using constant 
                $encapsulated = []; [cite: 1]
                $params = preg_split('/, /', $responseArray['headers'][self::HEADER_ENCAPSULATED]); // Using constant 

                if (count($params) > 0) { [cite: 1]
                    foreach ($params as $param) { [cite: 1]
                        $parts = preg_split('/=/', $param); [cite: 1]
                        if (count($parts) !== 2) { [cite: 1]
                            continue; [cite: 1]
                        }

                        $encapsulated[$parts[0]] = $parts[1]; [cite: 1]
                    }
                }

                foreach ($encapsulated as $section => $offset) { [cite: 1]
                    $data = substr($body[1], (int)$offset); // Cast offset to int
                    switch ($section) { [cite: 1]
                        case 'req-hdr': [cite: 1]
                        case 'res-hdr': [cite: 1]
                            $responseArray['body'][$section] = preg_split('/\r?\n\r?\n/', $data, 2)[0]; [cite: 1]
                            break;

                        case 'req-body': [cite: 1]
                        case 'res-body': [cite: 1]
                            $parts = preg_split('/\r?\n/', $data, 2); [cite: 1]
                            if (count($parts) === 2) { [cite: 1]
                                $responseArray['body'][$section] = substr($parts[1], 0, hexdec($parts[0])); [cite: 1]
                            }
                            break;
                    }
                }
            }
        }

        return $responseArray; [cite: 1]
    }
}