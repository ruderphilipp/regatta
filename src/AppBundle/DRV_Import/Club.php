<?php

namespace AppBundle\DRV_Import;

class Club
{
    public function __construct(\SimpleXMLElement $x) {
        $this->drv_id = (int)(string)$x['id'];
        $this->name = (string)$x->name;
        $this->location = (string)$x->ort;
        $this->shortname = (string)$x->kurzform;
        $this->abbreviation = (string)$x->lettern;
    }
}