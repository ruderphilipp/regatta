<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Type;

Type::overrideType('datetimetz', 'AppBundle\Doctrine\VarDateTimeWithMicroseconds');

/**
 * Timing information for a team in a race
 *
 * @ORM\Table(name="timetable")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TimingRepository")
 */
class Timing
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
     * @var string
     *
     * @ORM\Column(name="checkpoint", type="string", length=255)
     */
    private $checkpoint;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetimetz")
     */
    private $time;

    /**
     * @ORM\ManyToOne(targetEntity="Registration", inversedBy="timings")
     */
    private $registration;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $checkpoint
     * @return Timing
     */
    public function setCheckpoint($checkpoint)
    {
        $this->checkpoint = $checkpoint;
        return $this;
    }

    /**
     * @return string
     */
    public function getCheckpoint()
    {
        return $this->checkpoint;
    }

    /**
     * @param \DateTime $time
     * @return Timing
     */
    public function setTime(\DateTime $time)
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return Registration
     */
    public function getRegistration()
    {
        return $this->registration;
    }

    /**
     * @param Registration $registration
     * @return Timing
     */
    public function setRegistration(Registration $registration)
    {
        $this->registration = $registration;
        return $this;
    }
}