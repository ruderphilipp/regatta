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
     * @var string
     *
     * @ORM\Column(name="token", type="string", nullable=true)
     */
    private $token;

    /**
     * @var RacingGroup
     *
     * @ORM\ManyToOne(targetEntity="RacingGroup", inversedBy="sections")
     */
    private $racingGroup;

    /**
     * @var RaceSection
     *
     * @ORM\ManyToOne(targetEntity="RaceSection", inversedBy="groups")
     */
    private $section;

    const NOT_AT_START = 'not_at_start';

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
     * Set racingGroup
     *
     * @param RacingGroup $group
     * @return RacingGroupsPerSection
     */
    public function setRacingGroup($group)
    {
        $this->racingGroup = $group;

        return $this;
    }

    /**
     * Get racingGroups
     *
     * @return RacingGroup
     */
    public function getRacingGroup()
    {
        return $this->racingGroup;
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

    /**
     * Check if the group is already checked in to its race.
     *
     * @return bool
     */
    public function isCheckedIn()
    {
        return (!is_null($this->token) && !$this->isCancelled());
    }

    /**
     * Assign the token to a group when they are at the start (or before), so that
     * it can be uniquely identified when passing the goal.
     *
     * @param $token string e.g. RFID token or starting number
     * @return RacingGroupsPerSection
     */
    public function setCheckedIn($token)
    {
        if (is_null($token)) {
            throw new \InvalidArgumentException('Token must not be null!');
        } elseif('' == trim($token)) {
            throw new \InvalidArgumentException('Token must not be empty!');
        }

        $this->token = $token;

        return $this;
    }

    /**
     * Check if the group did not show up at the start of its race.
     *
     * @return bool
     */
    public function isCancelled()
    {
        return (!is_null($this->token) && self::NOT_AT_START == $this->token);
    }

    /**
     * Mark a group as not shown up at the start of their race.
     *
     * @return RacingGroupsPerSection
     */
    public function setCancelled()
    {
        $this->setCheckedIn(self::NOT_AT_START);
        return $this;
    }

    /**
     * Undo the "not at start" marker.
     *
     * @return RacingGroupsPerSection
     */
    public function undoCancelled()
    {
        if (!$this->isCancelled()) {
            throw new \InvalidArgumentException('Cannot undo a non-cancelled registration!');
        }

        $this->token = null;
        return $this;
    }
}

