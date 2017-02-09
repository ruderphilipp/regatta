<?php

namespace AppBundle\Entity;

use AppBundle\Twig\AppExtension;
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
        $validRegistrations = array();
        /** @var Registration $registration */
        foreach($this->registrations as $registration) {
            if ($registration->isValidForRace()) {
                $validRegistrations[$registration->getLane()] = $registration;
            }
        }
        // sort by lane
        ksort($validRegistrations);

        $result = new ArrayCollection();
        foreach ($validRegistrations as $registration) {
            $result->add($registration);
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
        if ($this->isFinished()) {
            return false;
        }

        $counter = 0;
        /** @var \AppBundle\Entity\Registration $g */
        foreach($this->getValidRegistrations() as $g) {
            if ($g->isCheckedIn() || $g->isNotAtStart()) {
                $counter += 1;
            }
        }

        return ((0 < $this->getValidRegistrations()->count())
            && ($this->getValidRegistrations()->count() == $counter));
    }

    public function isStarted()
    {
        return ($this->getStatus() == RaceSectionStatus::STARTED);
    }

    public function isFinished()
    {
        return ($this->getStatus() == RaceSectionStatus::FINISHED);
    }

    public function canTakeMoreTeams()
    {
        if ($this->isFinished() || $this->isStarted()) {
            return false;
        }
        if ($this->race->getMaxStarterPerSection() <= $this->getValidRegistrations()->count()) {
            return false;
        }
        return true;
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

    /**
     * Get the first free lane in this section.
     *
     * @return int
     * @throws \InvalidArgumentException if no free lane left
     */
    public function tryToGetFirstFreeLane()
    {
        if (!$this->canTakeMoreTeams()) {
            throw new \InvalidArgumentException(sprintf('Abteilung %d hat keine freie Bahn mehr...', $this->getNumber()));
        }
        $result = -1;

        // check if there is some space in the middle and if the lane
        // number is smaller than the total number of available lanes
        $max = $this->getRace()->getMaxStarterPerSection();
        for($lane = 1; $lane <= $max; $lane++) {
            // is the lane already in use?
            $inUse = false;
            /** @var Registration $team */
            foreach ($this->getValidRegistrations() as $team) {
                if ($team->getLane() == $lane) {
                    $inUse = true;
                    break;
                }
            }
            if (!$inUse) {
                $result = $lane;
                break;
            }
        }

        if (-1 == $result) {
            throw new \InvalidArgumentException(sprintf('Abteilung %d hat keine freie Bahn mehr...', $this->getNumber()));
        }

        return $result;
    }

    public function getWinner($conciderRunRace = false)
    {
        if (!$this->isFinished()) {
            throw new \InvalidArgumentException("Section not finished, yet! So not possible to return winning team!");
        }

        $ae = new AppExtension();
        $sorted = $ae->sortByPlace($this->getValidRegistrations(), $conciderRunRace);
        if (!key_exists("1", $sorted)) {
            throw new \InvalidArgumentException("Could not find any winning team in section {$this->getNumber()}!");
        } else {
            return $sorted["1"];
        }
    }
}

interface RaceSectionStatus
{
    const READY_TO_START = 'ready_to_start';
    const STARTED = 'started';
    const FINISHED = 'finished';
}
