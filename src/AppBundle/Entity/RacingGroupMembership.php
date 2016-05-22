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
     * @var RacingGroup
     *
     * @ORM\ManyToOne(targetEntity="RacingGroup", inversedBy="members")
     */
    private $group;

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
     * @param RacingGroup $group
     * @return RacingGroupMembership
     */
    public function setGroup(RacingGroup $group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return RacingGroup
     */
    public function getGroup()
    {
        return $this->group;
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

