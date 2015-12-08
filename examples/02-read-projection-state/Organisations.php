<?php

class Organisations implements Iterator
{
    private $items = [];

    public static function fromArray($data)
    {
        $collection = new static();
        foreach ($data as $key => $value) {
            $collection->items[$key] = Organisation::fromArray($value);
        }

        return $collection;
    }

    public function current()
    {
        return current($this->items);
    }

    public function next()
    {
        next($this->items);
    }

    public function key()
    {
        return key($this->items);
    }

    public function valid()
    {
        return current($this->items) !== false;
    }

    public function rewind()
    {
        reset($this->items);
    }

    private function __construct(array $items = [])
    {
        $this->items = $items;
    }

}
