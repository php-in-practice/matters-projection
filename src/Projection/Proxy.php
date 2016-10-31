<?php

namespace PhpInPractice\Matters\Projection;

interface Proxy
{
    public function query($projectionName, $partition = null);
}
