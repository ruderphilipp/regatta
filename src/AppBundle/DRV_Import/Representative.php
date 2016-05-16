<?php

namespace AppBundle\DRV_Import;

class Representative
{
    public function __construct(\SimpleXMLElement $x) {
        $this->id = (int)(string)$x['id'];
        $this->name = (string)$x->name;
        $this->email = (string)$x->email;
        if ('' != $x->phone) {
            $this->phone = (string)$x->phone;
        } else {
            $this->phone = null;
        }
    }
}