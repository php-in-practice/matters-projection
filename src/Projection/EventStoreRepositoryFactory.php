<?php

namespace PhpInPractice\Matters\Projection;

final class EventStoreRepositoryFactory implements RepositoryFactory
{
    /** @var Driver */
    private $projector;

    /** @var StateSerializer */
    private $projectionSerializer;

    public function __construct(Driver $projector, StateSerializer $projectionSerializer)
    {
        $this->projector            = $projector;
        $this->projectionSerializer = $projectionSerializer;
    }

    public function create($projectionClassName, $projectionName)
    {
        return new EventStoreRepository(
            $this->projector,
            $this->projectionSerializer,
            $projectionClassName,
            $projectionName
        );
    }
}
