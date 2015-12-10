<?php

namespace PhpInPractice\Matters\Projection;

use Mockery as m;

/**
 * @coversDefaultClass PhpInPractice\Matters\Projection\EventStoreRepositoryFactory
 * @covers ::__construct
 * @covers ::<private>
 */
final class EventStoreRepositoryFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var StateSerializer|m\MockInterface */
    private $resultSerializer;

    /** @var Driver|m\MockInterface */
    private $projectionsDriver;

    /** @var EventStoreRepositoryFactory */
    private $factory;

    public function setUp()
    {
        $this->resultSerializer = m::mock(StateSerializer::class);
        $this->projectionsDriver = m::mock(Driver::class);

        $this->factory = new EventStoreRepositoryFactory($this->projectionsDriver, $this->resultSerializer);
    }

    /**
     * @test
     * @covers ::create
     */
    public function it_should_create_a_repository_for_a_given_projection()
    {
        $className      = 'Class name';
        $projectionName = 'Projection name';

        $repository = $this->factory->create($className, $projectionName);

        $this->assertInstanceOf(EventStoreRepository::class, $repository);
        $this->assertAttributeSame($className, 'projectionClassName', $repository);
        $this->assertAttributeSame($projectionName, 'projectionName', $repository);
    }
}
