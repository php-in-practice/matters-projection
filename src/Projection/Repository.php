<?php
namespace PhpInPractice\Matters\Projection;

interface Repository
{
    public function result($partition = null);
}
