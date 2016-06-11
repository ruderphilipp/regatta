<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RacingGroupMembership
 *
 * @ORM\Table(name="racing_group_membership")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RacingGroupMembershipRepository")
 */
class RacingGroupMembership
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
     * @ORM\Column(name="position", type="smallint")
     */
    private $position;

    /**
     * @var bool
     *
     * @ORM\Column(name="isCox", type="boolean")
     */
    private $isCox;

    /**
     * @var Team
     *
     * @ORM\ManyToOne(targetEntity="Team", inversedBy="members")
     */
    private $team;

    /**
     * @var Membership
     *
     * @ORM\ManyToOne(targetEntity="Membership", inversedBy="groups")
     */
    private $membership;


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
     * Set position
     *
     * @param integer $position
     *
     * @return RacingGroupMembership
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set isCox
     *
     * @param boolean $isCox
     *
     * @return RacingGroupMembership
     */
    public function setIsCox($isCox)
    {
        $this->isCox = $isCox;

        return $this;
    }

    /**
     * Get isCox
     *
     * @return bool
     */
    public function getIsCox()
    {
        return $this->isCox;
    }

    /**
     * @param Team $team
     * @return RacingGroupMembership
     */
    public function setTeam(Team $team)
    {
        $this->team = $team;

        return $this;
    }

    /**
     * @return Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param Membership $membership
     * @return RacingGroupMembership
     */
    public function setMembership(Membership $membership)
    {
        $this->membership = $membership;

        return $this;
    }

    /**
     * @return Membership
     */
    public function getMembership()
    {
        return $this->membership;
    }
}

