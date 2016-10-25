<?php

namespace PhpInPractice\Matters\Projection\Driver;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery as m;

/**
 * @coversDefaultClass PhpInPractice\Matters\Projection\Driver\Proxy
 * @covers ::<private>
 */
class EventStoreProxyTest extends \PHPUnit_Framework_TestCase
{
    /** @var ClientInterface|m\MockInterface */
    private $httpClient;

    public function setUp()
    {
        $this->httpClient = m::mock(ClientInterface::class);
    }

    /**
     * @test
     * @covers ::forUrl
     */
    public function it_should_create_instance_given_a_proxy_url()
    {
        $proxy = EventStoreProxy::forUrl('127.0.0.1', $this->httpClient);

        $this->assertInstanceOf(EventStoreProxy::class, $proxy);
        $this->assertAttributeSame('127.0.0.1', 'url', $proxy);
    }

    /**
     * @test
     * @covers ::query
     */
    public function it_should_return_response_body_as_array()
    {
        $response = m::mock(Response::class);

        $this->httpClient->shouldReceive('request')
                         ->withArgs(['GET', '127.0.0.1/projection/name?q=partition'])
                         ->andReturn($response);

        $result = json_encode(['data' => 'projectionData']);

        $response->shouldReceive('getStatusCode')->andReturn('200');
        $response->shouldReceive('getBody')->andReturn($result);

        $proxy = EventStoreProxy::forUrl('127.0.0.1', $this->httpClient);
        $this->assertSame(['data' => 'projectionData'], $proxy->query('name', 'partition'));
    }

    /**
     * @test
     * @covers ::query
     */
    public function it_should_pass_no_partition_when_no_partition_available()
    {
        $response = m::mock(Response::class);

        $this->httpClient->shouldReceive('request')
            ->withArgs(['GET', '127.0.0.1/projection/name'])
            ->andReturn($response);

        $result = json_encode(['data' => 'projectionData']);

        $response->shouldReceive('getStatusCode')->andReturn('200');
        $response->shouldReceive('getBody')->andReturn($result);

        $proxy = EventStoreProxy::forUrl('127.0.0.1', $this->httpClient);
        $this->assertSame(['data' => 'projectionData'], $proxy->query('name'));
    }
}
