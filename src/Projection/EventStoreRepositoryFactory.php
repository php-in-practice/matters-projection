<?php

namespace PhpInPractice\Matters\Projection;

use PhpInPractice\Matters\ProjectionsDriver;

final class EventStoreRepositoryFactory implements RepositoryFactory
{
    /** @var ProjectionsDriver */
    private $projector;

    /** @var StateSerializer */
    private $projectionSerializer;

    public function __construct(
        ProjectionsDriver $projector,
        StateSerializer $projectionSerializer
    ) {
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
