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
     * @var ArrayCollection[RacingGroupsPerSection]
     *
     * @ORM\OneToMany(targetEntity="RacingGroupsPerSection", mappedBy="section")
     */
    private $groups;


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
     * Get groups
     *
     * @return ArrayCollection[RacingGroupsPerSection]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Get all groups that are not de-registered
     *
     * @return ArrayCollection[RacingGroupsPerSection]
     */
    public function getRegisteredGroups()
    {
        $result = new ArrayCollection();
        /** @var RacingGroupsPerSection $group */
        foreach($this->groups as $group) {
            if (!$group->isDeregistered()) {
                $result->add($group);
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
        /** @var \AppBundle\Entity\RacingGroupsPerSection $g */
        foreach($this->getGroups() as $g) {
            if ($g->isCheckedIn()) {
                $counter += 1;
            } elseif ($g->isCancelled()) {
                $counter += 1;
                $cancelled += 1;
            }
        }

        return ($this->getGroups()->count() == $counter && $cancelled != $counter);
    }
}

