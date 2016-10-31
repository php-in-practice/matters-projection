<?php

namespace PhpInPractice\Matters\Projection;

final class ProxyRepository implements Repository
{
    /** @var string */
    private $projectionClassName;

    /** @var string */
    private $projectionName;

    /** @var StateSerializer */
    private $resultSerializer;

    /** @var Proxy */
    private $proxy;

    public function __construct(
        Proxy $proxy,
        StateSerializer $resultSerializer,
        $projectionClassName,
        $projectionName = null
    ) {
        $this->proxy               = $proxy;
        $this->projectionClassName = $projectionClassName;
        $this->projectionName      = $projectionName ?: $this->normalizeClassName($projectionClassName);
        $this->resultSerializer    = $resultSerializer;
    }

    public function result($partition = null)
    {
        $data = $this->proxy->query($this->projectionName, $partition);

        return $this->resultSerializer->unserialize(
            $this->projectionClassName,
            $data
        );
    }

    /**
     * @param $fqcn
     *
     * @return string
     */
    private function normalizeClassName($fqcn)
    {
        return strtolower(str_replace('\\', '.', trim($fqcn, '\\/ _-')));
    }
}
