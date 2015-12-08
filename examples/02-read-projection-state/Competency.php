<?php

class Competency
{
    public function fromArray(array $data)
    {
        $competency = new static();
        $competency->id = $data['id'];
        $competency->organisationId = $data['id'];
        $competency->name = $data['name'];
    }
}
