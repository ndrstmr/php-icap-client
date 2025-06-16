<?php

use IcapClient\DTO\IcapRequest;
use IcapClient\IcapRequestFormatter;
use PHPUnit\Framework\TestCase;

class IcapRequestFormatterTest extends TestCase
{
    public function testFormat()
    {
        $request = new IcapRequest(
            'RESPMOD',
            'icap.test',
            'example',
            [],
            [
                'res-hdr' => "HTTP/1.1 200 OK\r\nServer: Test/0.0.1\r\nContent-Type: text/html\r\n\r\n",
                'res-body' => 'This is a test.'
            ]
        );

        $formatter = new IcapRequestFormatter();
        $result = $formatter->format($request);

        $expected = "RESPMOD icap://icap.test/example ICAP/1.0\r\n" .
            "Host: icap.test\r\n" .
            "User-Agent: PHP-ICAP-CLIENT/0.5.0\r\n" .
            "Connection: close\r\n" .
            "Encapsulated: res-hdr=0, res-body=64\r\n" .
            "\r\n" .
            "HTTP/1.1 200 OK\r\nServer: Test/0.0.1\r\nContent-Type: text/html\r\n\r\n" .
            "f\r\nThis is a test.\r\n0\r\n\r\n";

        $this->assertSame($expected, $result);
    }

    public function testHeaderInjectionIsPrevented()
    {
        $request = new IcapRequest(
            'OPTIONS',
            'icap.test',
            'example',
            [
                'X-Test' => "foo\r\nInjected: bar"
            ]
        );

        $formatter = new IcapRequestFormatter();
        $result = $formatter->format($request);

        $this->assertStringContainsString("X-Test: fooInjected: bar\r\n", $result);
        $this->assertStringNotContainsString("\nInjected:", $result);
    }
}
