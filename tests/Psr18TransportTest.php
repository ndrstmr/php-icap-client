<?php
namespace Ndrstmr\Icap\Tests;

use Ndrstmr\Icap\Transport\Psr18Transport;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;

class DummyClient implements ClientInterface
{
    public ?RequestInterface $request = null;
    private Response $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function sendRequest(RequestInterface $request): Response
    {
        $this->request = $request;
        return $this->response;
    }
}

class Psr18TransportTest extends TestCase
{
    public function testSendReturnsResponseBody()
    {
        $factory = new Psr17Factory();
        $response = new Response(200, [], 'BAR');
        $client = new DummyClient($response);
        $transport = new Psr18Transport('icap.test', 1344, $client, $factory, $factory);

        $result = $transport->send('FOO');

        $this->assertSame('FOO', (string) $client->request->getBody());
        $this->assertSame('BAR', $result);
    }

    public function testSendIterableCollectsChunks()
    {
        $factory = new Psr17Factory();
        $response = new Response(200, [], 'OUT');
        $client = new DummyClient($response);
        $transport = new Psr18Transport('icap.test', 1344, $client, $factory, $factory);

        $result = $transport->sendIterable(['A', 'B']);

        $this->assertSame('AB', (string) $client->request->getBody());
        $this->assertSame('OUT', $result);
    }
}
