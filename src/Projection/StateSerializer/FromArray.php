<?php

namespace PhpInPractice\Matters\Projection\StateSerializer;

use PhpInPractice\Matters\Projection\StateSerializer as StateSerializerInterface;

final class FromArray implements StateSerializerInterface
{
    public function unserialize($class, array $data)
    {
        $methodName = 'fromArray';
        $this->assertMethodExists($class, $methodName);

        return call_user_func([$class, $methodName], $data);
    }

    /**
     * @param string|object $object
     * @param string        $methodName
     */
    private function assertMethodExists($object, $methodName)
    {
        if (! method_exists($object, $methodName)) {
            $className = is_string($object) ? $object : get_class($object);
            throw new MissingMethodException(
                'Method "' . $methodName . '" does not exist on class/object "' . $className . '"'
            );
        }
    }
}
