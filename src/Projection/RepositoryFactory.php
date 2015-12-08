<?php
namespace PhpInPractice\Matters\Projection;

interface RepositoryFactory
{
    public function create($projectionClassName, $projectionName);
}
