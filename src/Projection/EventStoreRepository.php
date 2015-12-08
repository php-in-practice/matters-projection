<?php

namespace PhpInPractice\Matters\Projection;

use PhpInPractice\Matters\Projections;

final class EventStoreRepository implements Repository
{
    /** @var string */
    private $projectionClassName;

    /** @var string */
    private $projectionName;

    /** @var Projections */
    private $projections;

    /** @var StateSerializer */
    private $resultSerializer;

    public function __construct(
        Projections $projections,
        StateSerializer $resultSerializer,
        $projectionClassName,
        $projectionName = null
    ) {
        $this->projectionClassName = $projectionClassName;
        $this->projectionName      = $projectionName ?: $this->normalizeClassName($projectionClassName);
        $this->projections         = $projections;
        $this->resultSerializer    = $resultSerializer;
    }

    public function result($partition = null)
    {
        $definition = $this->projections->get($this->projectionName);

        return $this->resultSerializer->unserialize(
            $this->projectionClassName,
            $this->projections->result($definition, $partition)
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
