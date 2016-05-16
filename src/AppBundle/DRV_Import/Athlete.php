<?php

namespace AppBundle\DRV_Import;

class Athlete
{
    public function __construct(\SimpleXMLElement $x) {
        $this->club_id = (int)(string)$x['verein'];

        $this->drv_id = (string)$x['id'];
        $this->lastname = (string)$x->name;
        $this->firstname = (string)$x->vorname;
        $this->yearofbirth = (int)(string)$x->jahrgang;
        $this->is_female = ('w' == (string)$x->geschlecht);
    }
}