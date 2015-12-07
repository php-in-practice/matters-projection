<?php

namespace PhpInPractice\Matters\Projection;

use PhpInPractice\Matters\ProjectionsInterface;

final class Repository
{
    /** @var string */
    private $projectionClassName;

    /** @var string */
    private $projectionName;

    /** @var ProjectionsInterface */
    private $projector;

    /** @var StateSerializer */
    private $resultSerializer;

    public function __construct(
        ProjectionsInterface $projector,
        StateSerializer $resultSerializer,
        $projectionClassName,
        $projectionName = null
    ) {
        $this->projectionClassName = $projectionClassName;
        $this->projectionName      = $projectionName ?: $this->normalizeClassName($projectionClassName);
        $this->projector           = $projector;
        $this->resultSerializer    = $resultSerializer;
    }

    public function getResult()
    {
        return $this->resultSerializer->unserialize(
            $this->projectionClassName,
            $this->projector->getResult($this->projectionName)
        );
    }

    /**
     * @param $fqcn
     *
     * @return string
     */
    private function normalizeClassName($fqcn)
    {
        return strtolower(str_replace('\\', '.', $fqcn));
    }
}
