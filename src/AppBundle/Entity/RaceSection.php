<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * RaceSection
 *
 * @ORM\Table(name="race_section")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RaceSectionRepository")
 */
class RaceSection
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Counter of sections in a particular race (first, second, ...)
     *
     * @var int
     *
     * @ORM\Column(name="number", type="integer")
     */
    private $number;

    /**
     * @var Race
     *
     * @ORM\ManyToOne(targetEntity="Race", inversedBy="sections")
     */
    private $race;

    /**
     * @var ArrayCollection[Registration]
     *
     * @ORM\OneToMany(targetEntity="Registration", mappedBy="section")
     */
    private $registrations;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set number
     *
     * @param integer $number
     *
     * @return RaceSection
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set race
     *
     * @param Race $race
     *
     * @return RaceSection
     */
    public function setRace($race)
    {
        $this->race = $race;

        return $this;
    }

    /**
     * Get race
     *
     * @return Race
     */
    public function getRace()
    {
        return $this->race;
    }

    /**
     * Get all registrations for all teams in this section
     *
     * @return ArrayCollection[Registration]
     */
    public function getRegistrations()
    {
        return $this->registrations;
    }

    /**
     * Get the registrations for all teams that are not de-registered
     *
     * @return ArrayCollection[Registration]
     */
    public function getValidRegistrations()
    {
        $result = new ArrayCollection();
        /** @var Registration $registration */
        foreach($this->registrations as $registration) {
            if (!$registration->isDeregistered() && !$registration->hasChangedToNewRace()) {
                $result->add($registration);
            }
        }
        return $result;
    }

    /**
     * Are all competitors checked in or marked as <i>not at start</i> so that the race can start?
     *
     * @return bool <code>false</code> if all starters are marked as absent or not all of them checked in
     */
    public function isReadyToStart()
    {
        $counter = 0;
        $cancelled = 0;
        /** @var \AppBundle\Entity\Registration $g */
        foreach($this->getRegistrations() as $g) {
            if ($g->isCheckedIn()) {
                $counter += 1;
            } elseif ($g->isCancelled()) {
                $counter += 1;
                $cancelled += 1;
            }
        }

        return ($this->getRegistrations()->count() == $counter && $cancelled != $counter);
    }
}

