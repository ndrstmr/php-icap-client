# php-icap-client

This project is a fork of [nathan242/php-icap-client](https://github.com/nathan242/php-icap-client) originally created by **Nathan Patrizi**. The code base has been thoroughly modernised and hardened to provide a safer ICAP client library for contemporary PHP projects. Full credit goes to Nathan for laying the foundation.

## Overview

The library offers a simple way to interact with Internet Content Adaptation Protocol (ICAP) services from PHP. It includes helpers for crafting requests and parsing responses while keeping the socket layer pluggable.

## Requirements

 - PHP **8.2** or newer
- Composer for installing dependencies

## Installation

```
composer require nathan242/php-icap-client
```

## Basic Usage

```php
use Ndrstmr\Icap\IcapClient;

$icap = new IcapClient('127.0.0.1', 13440);
$result = $icap->options('example');
print_r($result);
```

`reqmod` and `respmod` helpers are also available for modifying requests and responses:

```php
$icap->reqmod('example', [
    'req-hdr'  => "POST /test HTTP/1.1\r\nHost: 127.0.0.1\r\n\r\n",
    'req-body' => 'This is another test.'
]);

$icap->respmod('example', [
    'res-hdr'  => "HTTP/1.1 200 OK\r\nServer: Test/0.0.1\r\nContent-Type: text/html\r\n\r\n",
    'res-body' => 'This is a test.'
]);
```

## Architecture

- **IcapClient** – high-level API that uses the formatter, parser and socket implementation.
- **IcapRequestFormatter** – turns request objects into raw ICAP strings.
- **IcapResponseParser** – parses server responses into structured objects.
- **Socket\*** – pluggable socket layer so you can provide your own transport. The
  socket interface exposes a `waitForData()` method used to efficiently wait for
  incoming bytes.
- Data transfer objects can be found under the `DTO` namespace.

### Timeout Handling

The transport resets the read timer every time data is received. Large responses
therefore do not trigger a timeout as long as more bytes keep arriving. If no
data becomes available within the configured timeout, an
`IcapTimeoutException` is thrown.

### Read Buffer Size

The transport reads data in chunks controlled by `setReadBufferSize()`. The
default buffer size is 8192 bytes. Reduce this value when working with
non‑blocking socket clients to limit how much data is read at a time.

### Non‑blocking Sockets

Custom implementations of `SocketClientInterface` may return immediately when no
data is available (for example using `socket_set_nonblock()` or `stream_select`).
The provided `PhpSocketClient` remains blocking by default but can be swapped
out for a non‑blocking variant.

## Running Tests

Execute the following command to run the test suite:

```
vendor/bin/phpunit
```

## License

Released under the MIT license. See `LICENSE` for details.
