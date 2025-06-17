<?php
namespace Ndrstmr\Icap\Tests;

use Ndrstmr\Icap\IcapResponseParser;
use PHPUnit\Framework\TestCase;

class IcapResponseParserTest extends TestCase
{
    public function testParse()
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

        $parser = new IcapResponseParser();
        $result = $parser->parse($response);

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

        $this->assertEquals($expected, [
            'protocol' => $result->protocol,
            'headers' => $result->headers,
            'body' => $result->body,
            'rawBody' => $result->rawBody,
        ]);
    }

    public function testInvalidResponseThrowsException()
    {
        $parser = new IcapResponseParser();
        $this->expectException(\Ndrstmr\Icap\Exception\IcapParseException::class);
        $parser->parse('BAD');
    }
}
