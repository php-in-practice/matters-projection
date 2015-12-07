<?php

namespace PhpInPractice\Matters\Projection\StateSerializer;

class FromArrayProjectionMock
{
    private $id;

    public function id()
    {
        return $this->id;
    }

    public static function fromArray(array $data)
    {
        $event = new static();
        $event->id = $data['id'];

        return $event;
    }
}
