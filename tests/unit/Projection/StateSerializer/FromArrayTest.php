<?php

namespace PhpInPractice\Matters\Projection\StateSerializer;

use Mockery as m;

/**
 * @coversDefaultClass PhpInPractice\Matters\Projection\StateSerializer\FromArray
 * @covers ::<private>
 */
class FromArrayTest extends \PHPUnit_Framework_TestCase
{
    /** @var FromArray */
    private $serializer;

    public function setUp()
    {
        $this->serializer = $serializer = new FromArray();
    }

    /**
     * @test
     * @covers ::unserialize
     */
    public function it_should_call_from_array_on_event_when_unserializing()
    {
        $expected = ['id' => 1];

        $event = $this->serializer->unserialize(FromArrayProjectionMock::class, $expected);

        $this->assertInstanceOf(FromArrayProjectionMock::class, $event);
        $this->assertSame($expected['id'], $event->id());
    }

    /**
     * @test
     * @covers ::unserialize
     * @expectedException \PhpInPractice\Matters\Projection\StateSerializer\MissingMethodException
     */
    public function it_should_throw_an_exception_if_the_from_array_method_does_not_exist()
    {
        $this->serializer->unserialize('stdClass', ['id' => 1]);
    }
}
