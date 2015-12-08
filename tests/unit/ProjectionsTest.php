<?php

namespace PhpInPractice\Matters;

use GuzzleHttp\ClientInterface;
use Mockery as m;

/**
 * @coversDefaultClass PhpInPractice\Matters\Projections
 * @covers ::<private>
 */
class ProjectionsTest extends \PHPUnit_Framework_TestCase
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

        $projections = EventStoreProjections::forUrl('127.0.0.1', $this->httpClient);

        $this->assertInstanceOf(EventStoreProjections::class, $projections);
        $this->assertAttributeSame('127.0.0.1', 'url', $projections);
    }

    /**
     * Verifies whether the send method is invoked once, meaning that the connection is checked.
     */
    private function thenConnectionIsChecked()
    {
        $this->httpClient->shouldReceive('send')->once();
    }
}
