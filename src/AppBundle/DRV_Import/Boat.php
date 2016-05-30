<?php

namespace AppBundle\DRV_Import;

use AppBundle\DRV_Import\Athlete;

class Boat
{
    private $positions = array();

    public function __construct(\SimpleXMLElement $x) {
        $this->id = (int)(string)$x['id'];
        $this->name = (string)$x->titel;
        if ('' != (string)$x->remark) {
            $this->remark = (string)$x->remark;
        } else {
            $this->remark = null;
        }

        $this->club_id = (int)(string)$x['verein'];
        $this->representative_id = (int)(string)$x['obmann'];
    }

    public function add(Athlete $a, $pos, $is_cox) {
        $this->positions[$pos] = array('athlete' => $a->drv_id, 'is_cox' => $is_cox);
    }

    public function getPositions() {
        return $this->positions;
    }
}