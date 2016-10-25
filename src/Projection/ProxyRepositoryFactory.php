<?php

namespace PhpInPractice\Matters\Projection;

final class ProxyRepositoryFactory implements RepositoryFactory
{
    /** @var Proxy */
    private $projector;

    /** @var StateSerializer */
    private $projectionSerializer;

    public function __construct(Proxy $projector, StateSerializer $projectionSerializer)
    {
        $this->projector            = $projector;
        $this->projectionSerializer = $projectionSerializer;
    }

    public function create($projectionClassName, $projectionName)
    {
        return new ProxyRepository(
            $this->projector,
            $this->projectionSerializer,
            $projectionClassName,
            $projectionName
        );
    }
}
