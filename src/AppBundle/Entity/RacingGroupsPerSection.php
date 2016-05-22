<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * RacingGroupsPerSection
 *
 * @ORM\Table(name="racing_groups_per_section")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RacingGroupsPerSectionRepository")
 */
class RacingGroupsPerSection
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
     * @var int
     *
     * @ORM\Column(name="lane", type="integer")
     */
    private $lane;

    /**
     * @var ArrayCollection[RacingGroup]
     *
     * @ORM\OneToMany(targetEntity="RacingGroup", mappedBy="section")
     */
    private $racingGroups;

    /**
     * @var RaceSection
     *
     * @ORM\ManyToOne(targetEntity="RaceSection", inversedBy="groups")
     */
    private $section;


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
     * Set lane
     *
     * @param integer $lane
     *
     * @return RacingGroupsPerSection
     */
    public function setLane($lane)
    {
        $this->lane = $lane;

        return $this;
    }

    /**
     * Get lane
     *
     * @return int
     */
    public function getLane()
    {
        return $this->lane;
    }

    /**
     * Get racingGroups
     *
     * @return ArrayCollection[RacingGroup]
     */
    public function getRacingGroups()
    {
        return $this->racingGroups;
    }

    /**
     * Set section
     *
     * @param RaceSection $section
     *
     * @return RacingGroupsPerSection
     */
    public function setSection($section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * Get section
     *
     * @return RaceSection
     */
    public function getSection()
    {
        return $this->section;
    }
}

