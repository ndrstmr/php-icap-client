<?php
namespace Ndrstmr\Icap\Tests;

use Ndrstmr\Icap\DTO\IcapRequest;
use Ndrstmr\Icap\IcapRequestFormatter;
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

    public function testHeaderNameInjectionIsSanitized()
    {
        $request = new IcapRequest(
            'OPTIONS',
            'icap.test',
            'example',
            [
                "X-Te\nst" => 'bar'
            ]
        );

        $formatter = new IcapRequestFormatter();
        $result = $formatter->format($request);

        $this->assertStringContainsString("X-Test: bar\r\n", $result);
    }

    public function testFormatIterableMatchesFormat()
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
        $expected = $formatter->format($request);
        $result = '';
        foreach ($formatter->formatIterable($request) as $chunk) {
            $result .= $chunk;
        }

        $this->assertSame($expected, $result);
    }

    public function testStreamingBodyGenerator()
    {
        $generator = static function () {
            yield 'This ';
            yield 'is ';
            yield 'a ';
            yield 'test.';
        };

        $request = new IcapRequest(
            'RESPMOD',
            'icap.test',
            'example',
            [],
            [
                'res-hdr' => "HTTP/1.1 200 OK\r\nServer: Test/0.0.1\r\nContent-Type: text/html\r\n\r\n",
                'res-body' => $generator(),
            ]
        );

        $formatter = new IcapRequestFormatter();

        $expected = "RESPMOD icap://icap.test/example ICAP/1.0\r\n" .
            "Host: icap.test\r\n" .
            "User-Agent: PHP-ICAP-CLIENT/0.5.0\r\n" .
            "Connection: close\r\n" .
            "Encapsulated: res-hdr=0, res-body=64\r\n" .
            "\r\n" .
            "HTTP/1.1 200 OK\r\n" .
            "Server: Test/0.0.1\r\n" .
            "Content-Type: text/html\r\n" .
            "\r\n" .
            "5\r\nThis \r\n3\r\nis \r\n2\r\na \r\n5\r\ntest.\r\n0\r\n\r\n";

        $result = '';
        foreach ($formatter->formatIterable($request) as $chunk) {
            $result .= $chunk;
        }

        $this->assertSame($expected, $result);
    }
}
