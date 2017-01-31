<?php

namespace AppBundle\Entity;

use \AppBundle\Entity\Club;
use \AppBundle\Entity\Registration;
use \Doctrine\Common\Collections\ArrayCollection;

class CheckInsPerClub
{
    /** @var Club */
    protected $club;

    /** @var ArrayCollection */
    protected $registrations = null;

    public function __construct(Club $club)
    {
        if (is_null($club)) {
            throw new InvalidArgumentException("No null allowed!");
        }
        $this->club = $club;
        $this->registrations = new ArrayCollection();
    }

    /**
     * @return Club The club
     */
    public function getClub()
    {
        return $this->club;
    }

    /**
     * Get all checked-in registrations.
     *
     * @return ArrayCollection[Registration] All registrations of this club.
     */
    public function getRegistrations()
    {
        // do not out duplicates if the team is the same for multiple registrations
        $teams = array();
        $result = new ArrayCollection();
        /** @var Registration $registration */
        foreach ($this->registrations as $registration) {
            $myId = $registration->getTeam()->getId();
            if (!in_array($myId, $teams)) {
                $result->add($registration);
                $teams[] = $myId;
            }
        }
        return $result;
    }

    public function addRegistration(Registration $registration)
    {
        if (is_null($registration)) {
            throw new InvalidArgumentException("No null allowed!");
        }
        $this->registrations->add($registration);
    }

    public function count()
    {
        return $this->registrations->count();
    }

    public function __toString()
    {
        $ids = array();
        foreach ($this->registrations as $r) {
            $ids[] = $r->getId();
        }
        sort($ids);
        return "CheckInsPerClub[" . $this->club->getName() . ": ("  . implode(",", $ids) . ")]";
    }
}