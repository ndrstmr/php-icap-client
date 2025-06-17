<?php
namespace Ndrstmr\Icap\Tests;

use Ndrstmr\Icap\IcapClientFactory;
use Ndrstmr\Icap\IcapClientConfig;
use Ndrstmr\Icap\Socket\TlsSocketConnection;
use Ndrstmr\Icap\Socket\PhpSocketClient;
use PHPUnit\Framework\TestCase;

class IcapClientFactoryTest extends TestCase
{
    public function testCreateDefaultReturnsPhpSocketClient()
    {
        $client = IcapClientFactory::create('icap.test', 1344);
        $ref = new \ReflectionClass($client);
        $prop = $ref->getProperty('transport');
        $prop->setAccessible(true);
        $transport = $prop->getValue($client);
        $refTrans = new \ReflectionClass($transport);
        $sprop = $refTrans->getProperty('socketClient');
        $sprop->setAccessible(true);
        $socket = $sprop->getValue($transport);
        $this->assertInstanceOf(PhpSocketClient::class, $socket);
    }

    public function testCreateWithTlsReturnsTlsSocketConnection()
    {
        $client = IcapClientFactory::create('icap.test', 1344, true);
        $ref = new \ReflectionClass($client);
        $prop = $ref->getProperty('transport');
        $prop->setAccessible(true);
        $transport = $prop->getValue($client);
        $refTrans = new \ReflectionClass($transport);
        $sprop = $refTrans->getProperty('socketClient');
        $sprop->setAccessible(true);
        $socket = $sprop->getValue($transport);
        $this->assertInstanceOf(TlsSocketConnection::class, $socket);
    }

    public function testCreateAppliesConfiguration()
    {
        $config = new IcapClientConfig(1, 'TEST-UA', 1.5, 1024, true);
        $client = IcapClientFactory::create('icap.test', 1344, false, $config);

        $this->assertSame('TEST-UA', $client->getUserAgent());
        $this->assertSame(1, $client->getMaxResponseSize());
        $this->assertSame(1.5, $client->getReadTimeout());
        $this->assertSame(1024, $client->getReadBufferSize());
        $this->assertTrue($client->isPersistentConnection());
    }
}
