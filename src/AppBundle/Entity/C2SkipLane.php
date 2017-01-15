<?php

namespace AppBundle\Entity;

class C2SkipLane extends C2ErgoInfo
{

    /**
     * Use this if you need an empty lane in the VRA export file
     */
    public function __construct()
    {
        parent::__construct(0, '.', 'skip');
    }
}