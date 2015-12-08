<?php


class Organisation
{
    private $id;
    private $name;

    public static function fromArray(array $data)
    {
        $organisation = new static();
        $organisation->id = $data['id'];
        $organisation->name = $data['name'];

        return $organisation;
    }

    private function __construct()
    {

    }
}
