<?php

namespace AppBundle\DRV_Import;

use AppBundle\DRV_Import\Boat;

class Race
{
    /** @var array[Boat] $boats */
    private $boats = array();

    public function __construct(\SimpleXMLElement $x) {
        $this->number = (string)$x['nummer'];
        $this->specification = (string) $x['spezifikation'];
        $this->extra = (string) $x['zusatz'];
    }

    public function add(Boat $boat) {
        $this->boats[] = $boat;
    }

    public function getBoats() {
        return $this->boats;
    }
}