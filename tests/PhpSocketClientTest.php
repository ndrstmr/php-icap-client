<?php
namespace Ndrstmr\Icap\Tests;

use Ndrstmr\Icap\Socket\PhpSocketClient;
use PHPUnit\Framework\TestCase;

class PhpSocketClientTest extends TestCase
{
    public function testConnectWriteReadDisconnect()
    {
        if (!function_exists('socket_create')) {
            $this->markTestSkipped('Sockets extension not available');
        }

        // create server socket
        $server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($server, '127.0.0.1', 0);
        socket_getsockname($server, $address, $port);
        socket_listen($server, 1);

        $client = new PhpSocketClient();
        $this->assertTrue($client->connect($address, $port));

        $peer = socket_accept($server);

        $client->write('hello');
        $this->assertSame('hello', socket_read($peer, 5));

        socket_write($peer, 'world');
        $this->assertSame('world', $client->read(5));

        $client->disconnect();
        socket_close($peer);
        socket_close($server);

        $this->assertIsInt($client->getLastError());
    }

    public function testTimeoutConfiguration()
    {
        $client = new PhpSocketClient(1.5, 2.5);
        $this->assertSame(1.5, $client->getReadTimeout());
        $this->assertSame(2.5, $client->getWriteTimeout());

        $client->setReadTimeout(3.0);
        $this->assertSame(3.0, $client->getReadTimeout());

        $client->setWriteTimeout(4.0);
        $this->assertSame(4.0, $client->getWriteTimeout());
    }

    public function testWaitForData()
    {
        if (!function_exists('socket_create')) {
            $this->markTestSkipped('Sockets extension not available');
        }

        $server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($server, '127.0.0.1', 0);
        socket_getsockname($server, $address, $port);
        socket_listen($server, 1);

        $client = new PhpSocketClient();
        $this->assertTrue($client->connect($address, $port));

        $peer = socket_accept($server);

        $this->assertFalse($client->waitForData(0.1));

        socket_write($peer, 'abc');
        $this->assertTrue($client->waitForData(0.1));
        $this->assertSame('abc', $client->read(3));

        $client->disconnect();
        socket_close($peer);
        socket_close($server);
    }
}
