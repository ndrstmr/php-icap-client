<?php

use IcapClient\IcapClient;
use IcapClient\Socket\PhpSocketClient;
use IcapClient\Transport\IcapTransport;
use PHPUnit\Framework\TestCase;

class IcapClientTest extends TestCase
{
    public function testGetRequest()
    {
        $transport = new IcapTransport('icap.test', 1344, new PhpSocketClient());
        $client = new IcapClient('icap.test', 1344, $transport);
        $body = [
            'res-hdr' => "HTTP/1.1 200 OK\r\nServer: Test/0.0.1\r\nContent-Type: text/html\r\n\r\n",
            'res-body' => 'This is a test.'
        ];

        $expected = "RESPMOD icap://icap.test/example ICAP/1.0\r\n" .
            "Host: icap.test\r\n" .
            "User-Agent: PHP-ICAP-CLIENT/0.5.0\r\n" .
            "Connection: close\r\n" .
            "Encapsulated: res-hdr=0, res-body=64\r\n" .
            "\r\n" .
            "HTTP/1.1 200 OK\r\nServer: Test/0.0.1\r\nContent-Type: text/html\r\n\r\n" .
            "f\r\nThis is a test.\r\n0\r\n\r\n";

        $this->assertSame($expected, $client->getRequest('RESPMOD', 'example', $body));
    }

    public function testParseResponse()
    {
        $response = "ICAP/1.0 200 OK\r\n" .
            "Date: Wed, 03 Jul 2019 22:11:33 GMT\r\n" .
            "ISTag: testtag\r\n" .
            "Encapsulated: res-hdr=0, res-body=64\r\n" .
            "Server: Python-ICAP/1.0\r\n" .
            "\r\n" .
            "HTTP/1.1 200 OK\r\n" .
            "content-type: text/html\r\n" .
            "server: Test/0.0.1\r\n" .
            "\r\n" .
            "f\r\nThis is a test.\r\n0\r\n\r\n";

        $transport = new IcapTransport('icap.test', 1344, new PhpSocketClient());
        $client = new IcapClient('icap.test', 1344, $transport);
        $ref = new ReflectionClass($client);
        $method = $ref->getMethod('parseResponse');
        $method->setAccessible(true);
        $result = $method->invoke($client, $response);

        $expected = [
            'protocol' => [
                'icap' => 'ICAP/1.0',
                'code' => '200',
                'message' => 'OK',
            ],
            'headers' => [
                'Date' => 'Wed, 03 Jul 2019 22:11:33 GMT',
                'ISTag' => 'testtag',
                'Encapsulated' => 'res-hdr=0, res-body=64',
                'Server' => 'Python-ICAP/1.0',
            ],
            'body' => [
                'res-hdr' => "HTTP/1.1 200 OK\r\ncontent-type: text/html\r\nserver: Test/0.0.1",
                'res-body' => 'This is a test.',
            ],
            'rawBody' => "HTTP/1.1 200 OK\r\ncontent-type: text/html\r\nserver: Test/0.0.1\r\n\r\nf\r\nThis is a test.\r\n0\r\n\r\n",
        ];

        $this->assertEquals($expected, $result);
    }

    public function testReadFileThrowsException()
    {
        $transport = new IcapTransport('icap.test', 1344, new PhpSocketClient());
        $client = new IcapClient('icap.test', 1344, $transport);
        $ref = new ReflectionClass($client);
        $method = $ref->getMethod('readFile');
        $method->setAccessible(true);

        $this->expectException(\IcapClient\Exception\IcapFileException::class);
        @$method->invoke($client, '/does/not/exist');
    }
}
