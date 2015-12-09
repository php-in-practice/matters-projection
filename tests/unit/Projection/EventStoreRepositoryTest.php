<?php

namespace PhpInPractice\Matters\Projection;

use PhpInPractice\Matters\ProjectionsDriver;
use Mockery as m;

/**
 * @coversDefaultClass PhpInPractice\Matters\Projection\EventStoreRepository
 * @covers ::<private>
 */
final class EventStoreRepositoryTest extends \PHPUnit_Framework_TestCase
{
    const EXAMPLE_PROJECTION_NAME = 'ExampleProjectionName';

    const EXAMPLE_CLASS_NAME = 'ExampleClassName';

    /** @var StateSerializer|m\MockInterface */
    private $resultSerializer;

    /** @var ProjectionsDriver|m\MockInterface */
    private $projectionsDriver;

    /** @var EventStoreRepository */
    private $repository;

    public function setUp()
    {
        $this->resultSerializer = m::mock(StateSerializer::class);
        $this->projectionsDriver = m::mock(ProjectionsDriver::class);

        $this->repository = new EventStoreRepository(
            $this->projectionsDriver,
            $this->resultSerializer,
            self::EXAMPLE_CLASS_NAME,
            self::EXAMPLE_PROJECTION_NAME
        );
    }

    /**
     * @test
     * @covers ::__construct
     */
    public function it_should_use_a_normalized_class_name_as_projection_name_when_projection_is_omitted()
    {
        $expected = 'my.classname';

        $repository = new EventStoreRepository(
            $this->projectionsDriver,
            $this->resultSerializer,
            '/ \\My\ClassName\\ _/-' // an impossible name but it demonstrates trimming
        );

        $this->assertAttributeSame($expected, 'projectionName', $repository);
    }

    /**
     * @test
     * @covers ::result
     */
    public function it_should_retrieve_the_results_as_an_instance_of_a_value_object()
    {
        $definition = Definition::createNew(self::EXAMPLE_PROJECTION_NAME, '');
        $result     = ['abc'];
        $expected   = 'expected';

        $this->givenDriverReturnsDefinition($definition);
        $this->thenDriverReturnsResult($definition, $result);
        $this->thenSerializerTransformsResultIntoAValueObject(self::EXAMPLE_CLASS_NAME, $result, $expected);

        $this->assertSame($expected, $this->repository->result());
    }

    /**
     * @test
     * @covers ::result
     */
    public function it_should_retrieve_a_partition_of_the_results_as_an_instance_of_a_value_object()
    {
        $definition = Definition::createNew(self::EXAMPLE_PROJECTION_NAME, '');
        $result     = ['abc'];
        $expected   = 'expected';
        $partition  = 'streamName-Id';

        $this->givenDriverReturnsDefinition($definition);
        $this->thenDriverReturnsResult($definition, $result, $partition);
        $this->thenSerializerTransformsResultIntoAValueObject(self::EXAMPLE_CLASS_NAME, $result, $expected);

        $this->assertSame($expected, $this->repository->result($partition));
    }

    private function givenDriverReturnsDefinition($definition)
    {
        $this->projectionsDriver
            ->shouldReceive('get')->once()
            ->with(self::EXAMPLE_PROJECTION_NAME)
            ->andReturn($definition)
        ;
    }

    private function thenDriverReturnsResult($definition, $state, $partition = null)
    {
        $this->projectionsDriver
            ->shouldReceive('result')->once()
            ->with($definition, $partition)
            ->andReturn($state)
        ;
    }

    private function thenSerializerTransformsResultIntoAValueObject($className, $result, $expected)
    {
        $this->resultSerializer
            ->shouldReceive('unserialize')->once()
            ->with($className, $result)
            ->andReturn($expected)
        ;
    }
}
