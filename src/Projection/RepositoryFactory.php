<?php

namespace PhpInPractice\Matters\Projection;

use PhpInPractice\Matters\ProjectionsInterface;

final class RepositoryFactory
{
    /** @var ProjectionsInterface */
    private $projector;

    /** @var StateSerializer */
    private $projectionSerializer;

    public function __construct(
        ProjectionsInterface $projector,
        StateSerializer $projectionSerializer
    ) {
        $this->projector            = $projector;
        $this->projectionSerializer = $projectionSerializer;
    }

    public function create($projectionClassname, $projectionName)
    {
        return new Repository(
            $this->projector,
            $this->projectionSerializer,
            $projectionClassname,
            $projectionName
        );
    }
}
