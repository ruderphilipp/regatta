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
     * Status of this specific section
     *
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, nullable=true)
     */
    private $status;

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
     * Set the status of this section
     *
     * @param string $status The new status of this section
     *
     * @return RaceSection
     * @see RaceSectionStatus
     */
    public function setStatus($status)
    {
        // validate the the given status is one of RaceSectionStatus constants
        $oClass = new \ReflectionClass(RaceSectionStatus::class);
        $found = false;
        foreach(array_values($oClass->getConstants()) as $c) {
            if ($c == $status) {
                $found = true;
            }
        }
        if (!$found) {
            throw new \InvalidArgumentException('Given status is not valid!');
        }
        $this->status = $status;

        return $this;
    }

    /**
     * Get the status of this section
     *
     * @return string status
     * @see RaceSectionStatus
     */
    public function getStatus()
    {
        return $this->status;
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
            if ($registration->isValidForRace()) {
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
        foreach($this->getValidRegistrations() as $g) {
            if ($g->isCheckedIn()) {
                $counter += 1;
            } elseif ($g->isCancelled()) {
                $counter += 1;
                $cancelled += 1;
            }
        }

        return (0 < $this->getValidRegistrations()->count()) &&
               ($this->getRegistrations()->count() == $counter && $cancelled != $counter);
    }

    public function isStarted()
    {
        return ($this->getStatus() == RaceSectionStatus::STARTED);
    }

    public function isFinished()
    {
        return ($this->getStatus() == RaceSectionStatus::FINISHED);
    }

    /**
     * @return \DateTime Starting time of this section
     * @throws \InvalidArgumentException if this section was not started, yet
     */
    public function getStartTime()
    {
        if (!$this->isStarted() && !$this->isFinished()) {
            throw new \InvalidArgumentException('Only those section that where started have a starting time!');
        }
        // get first start timing, since all have the same start time
        /** @var Registration $registration */
        foreach ($this->getValidRegistrations() as $registration) {
            /** @var Timing $timing */
            foreach ($registration->getTimings() as $timing) {
                if ($timing->getCheckpoint() == Registration::CHECKPOINT_START) {
                    return $timing->getTime();
                }
            }
        }
    }

    /**
     * @return \DateTime Finishing time of the latest team.
     * @throws \InvalidArgumentException if this section is not finished, yet
     */
    public function getLatestFinishingTime()
    {
        if (!$this->isFinished()) {
            throw new \InvalidArgumentException('Only those section that are finished have a finishing time!');
        }
        $latest = new \DateTime('@0'); // unix timestamp: 1970-1-1 0:00:00
        /** @var Registration $registration */
        foreach ($this->getValidRegistrations() as $registration) {
            /** @var Timing $timing */
            foreach ($registration->getTimings() as $timing) {
                if ($timing->getCheckpoint() == Registration::CHECKPOINT_FINISH) {
                    if ($latest <  $timing->getTime()) {
                        $latest = $timing->getTime();
                    }
                }
            }
        }
        return $latest;
    }
}

interface RaceSectionStatus
{
    const READY_TO_START = 'ready_to_start';
    const STARTED = 'started';
    const FINISHED = 'finished';
}
