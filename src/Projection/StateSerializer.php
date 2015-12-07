<?php

namespace PhpInPractice\Matters\Projection;

interface StateSerializer
{
    public function unserialize($class, array $data);
}
