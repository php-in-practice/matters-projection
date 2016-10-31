<?php

namespace PhpInPractice\Matters\Projection;

use Mockery as m;

/**
 * @coversDefaultClass PhpInPractice\Matters\Projection\ProxyRepository
 * @covers ::<private>
 */
final class ProxyRepositoryTest extends \PHPUnit_Framework_TestCase
{
    const EXAMPLE_PROJECTION_NAME = 'ExampleProjectionName';

    const EXAMPLE_CLASS_NAME = 'ExampleClassName';

    /** @var StateSerializer|m\MockInterface */
    private $resultSerializer;

    /** @var Proxy|m\MockInterface */
    private $proxy;

    /** @var ProxyRepository */
    private $repository;

    public function setUp()
    {
        $this->resultSerializer = m::mock(StateSerializer::class);
        $this->proxy = m::mock(Proxy::class);

        $this->repository = new ProxyRepository(
            $this->proxy,
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

        $repository = new ProxyRepository(
            $this->proxy,
            $this->resultSerializer,
            '/ \\My\ClassName\\ _/-' // an impossible name but it demonstrates trimming
        );

        $this->assertAttributeSame($expected, 'projectionName', $repository);
    }

    /**
     * @test
     * @covers ::__construct
     */
    public function it_should_use_a_projection_name_when_projection_is_provided()
    {
        $expected = self::EXAMPLE_PROJECTION_NAME;

        $repository = new ProxyRepository(
            $this->proxy,
            $this->resultSerializer,
            self::EXAMPLE_CLASS_NAME,
            self::EXAMPLE_PROJECTION_NAME
        );

        $this->assertAttributeSame($expected, 'projectionName', $repository);
    }

    /**
     * @test
     * @covers ::result
     */
    public function it_should_retrieve_a_partition_of_the_results_as_an_instance_of_a_value_object()
    {
        $partition = 'partition';

        $this->proxy
            ->shouldReceive('query')
            ->once()
            ->withArgs([self::EXAMPLE_PROJECTION_NAME, $partition])
            ->andReturn(['something']);

        $this->resultSerializer
            ->shouldReceive('unserialize')
            ->once()
            ->with(self::EXAMPLE_CLASS_NAME, ['something'])
            ->andReturn('something else');

        $this->assertSame('something else', $this->repository->result($partition));
    }

    /**
     * @test
     * @covers ::result
     */
    public function it_should_retrieve_results_as_an_instance_of_a_value_object_when_no_partition_is_provided()
    {
        $this->proxy
            ->shouldReceive('query')
            ->once()
            ->withArgs([self::EXAMPLE_PROJECTION_NAME, null])
            ->andReturn(['something']);

        $this->resultSerializer
            ->shouldReceive('unserialize')
            ->once()
            ->with(self::EXAMPLE_CLASS_NAME, ['something'])
            ->andReturn('something else');

        $this->assertSame('something else', $this->repository->result());
    }
}
