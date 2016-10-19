<?php

namespace PhpInPractice\Matters\Projection;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use PhpInPractice\Matters\Projection\Driver\EventStore;
use PhpInPractice\Matters\Projection\Driver\InvalidResponseException;

/**
 * @coversDefaultClass PhpInPractice\Matters\Projection\Driver\EventStore
 * @covers ::<private>
 */
class EventStoreProjectionsDriverTest extends \PHPUnit_Framework_TestCase
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
    public function it_should_create_instance_given_an_eventstore_url()
    {
        $this->thenConnectionIsChecked();

        $projections = EventStore::forUrl('127.0.0.1', $this->httpClient);

        $this->assertInstanceOf(EventStore::class, $projections);
        $this->assertAttributeSame('127.0.0.1', 'url', $projections);
    }

    /**
     * @test
     * @covers ::result
     */
    public function it_should_return_response_body_as_array()
    {
        $this->thenConnectionIsChecked();
        $expected = ['this' => 'is a test'];
        $response = json_encode($expected);

        $projections = EventStore::forUrl('127.0.0.1', $this->httpClient);

        $this->httpClient->shouldReceive('send')->once()
            ->with(m::on(function (Request $request) {
                return $request->getMethod() == 'GET' && (string)$request->getUri() === 'result-url';
            }))
            ->andReturn(new Response(200, [], $response));

        $this->assertSame($expected, $projections->result(Definition::fromEventStore([
            'name' => '',
            'mode' => '',
            'status' => '',
            'progress' => '',
            'stateUrl' => '',
            'resultUrl' => 'result-url',
            'queryUrl' => '',
            'enableCommandUrl' => '',
            'disableCommandUrl' => ''
        ])));
    }

    /**
     * @test
     * @covers ::result
     */
    public function it_should_error_when_an_invalid_response_is_returned_by_the_eventstore()
    {
        $this->setExpectedException(InvalidResponseException::class);
        $this->thenConnectionIsChecked();
        $invalidResponse = '';

        $projections = EventStore::forUrl('127.0.0.1', $this->httpClient);

        $this->httpClient->shouldReceive('send')->once()
            ->with(m::on(function (Request $request) {
                return $request->getMethod() == 'GET' && (string)$request->getUri() === 'result-url';
            }))
            ->andReturn(new Response(200, [], $invalidResponse));

        $projections->result(Definition::fromEventStore([
            'name' => '',
            'mode' => '',
            'status' => '',
            'progress' => '',
            'stateUrl' => '',
            'resultUrl' => 'result-url',
            'queryUrl' => '',
            'enableCommandUrl' => '',
            'disableCommandUrl' => ''
        ]));
    }

    /**
     * Verifies whether the send method is invoked once, meaning that the connection is checked.
     */
    private function thenConnectionIsChecked()
    {
        $this->httpClient->shouldReceive('send')->once()->with(m::on(function (Request $request){
            return $request->getMethod() == 'GET' && (string)$request->getUri() === '127.0.0.1';
        }));
    }
}
