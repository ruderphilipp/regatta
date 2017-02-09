<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Registration
 *
 * @ORM\Table(name="registrations")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RegistrationRepository")
 */
class Registration
{
    // Constants for the checkpoints
    const CHECKPOINT_START = 'Start';
    const CHECKPOINT_FINISH = 'Finish';

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
     * @ORM\Column(name="status", type="string", nullable=true)
     */
    private $registrationStatus;

    /**
     * @var Race
     *
     * @ORM\ManyToOne(targetEntity="Race")
     */
    private $changedRace;

    /**
     * @var string
     *
     * @ORM\Column(name="change_status", type="string", nullable=true)
     */
    private $changeStatus;

    /**
     * @var Team
     *
     * @ORM\ManyToOne(targetEntity="Team", inversedBy="sections")
     */
    private $team;

    /**
     * @var RaceSection
     *
     * @ORM\ManyToOne(targetEntity="RaceSection", inversedBy="registrations")
     */
    private $section;

    /**
     * @var ArrayCollection[Timing]
     *
     * @ORM\OneToMany(targetEntity="Timing", mappedBy="registration")
     */
    private $timings;

    // no attendance
    const NOT_AT_START = 'not_at_start';
    const DE_REGISTERED = 'de-registered';
    // from/to another race
    const CHANGED_TO = 'changed_to';
    const CHANGED_FROM = 'changed_from';
    // during race
    const STARTED = 'started';
    const FINISHED = 'finished';
    const ABORTED = 'aborted';

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
     * @return Registration
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
     * Set team
     *
     * @param Team $team
     * @return Registration
     */
    public function setTeam(Team $team)
    {
        $this->team = $team;

        return $this;
    }

    /**
     * Get the team
     *
     * @return Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Set section
     *
     * @param RaceSection $section
     *
     * @return Registration
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
     * Get all timings for this registration
     *
     * @return ArrayCollection[Timing]
     */
    public function getTimings()
    {
        return $this->timings;
    }

    /**
     * Check if the team is already checked in to its race.
     *
     * @return bool
     */
    public function isCheckedIn()
    {
        if (is_null($this->team)) {
            throw new \RuntimeException("No team found!");
        }
        return (($this->team->isCheckedIn() && !$this->isNotAtStart() && !$this->isDeregistered()) || $this->isFinished());
    }

    /**
     * Check if the team did not show up at the start of its race.
     *
     * @return bool
     */
    public function isNotAtStart()
    {
        return (self::NOT_AT_START == $this->registrationStatus);
    }

    /**
     * Mark a team as not shown up at the start of their race.
     *
     * @return Registration
     */
    public function setNotAtStart()
    {
        $this->registrationStatus = self::NOT_AT_START;
        return $this;
    }

    /**
     * Undo the "not at start" marker.
     *
     * @return Registration
     */
    public function undoNotAtStart()
    {
        if (!$this->isNotAtStart()) {
            throw new \InvalidArgumentException('Cannot undo a non-cancelled registration!');
        }

        $this->registrationStatus = null;
        return $this;
    }

    /**
     * Mark a team as not any longer participating in the entire race.
     *
     * @return Registration
     */
    public function setDeregistered()
    {
        $this->registrationStatus = self::DE_REGISTERED;
        // remove lane since the team is no longer in the race
        $this->lane = -1;
        return $this;
    }

    /**
     * Check if the team cancelled their whole registration for this race.
     *
     * @return bool
     */
    public function isDeregistered()
    {
        return (self::DE_REGISTERED == $this->registrationStatus);
    }

    /**
     * Undo de-registration of a team.
     *
     * @param RaceSection $section New section where the team should now start.
     * @param int $laneNumber Number of the lane in section.
     * @return Registration
     * @see Registration#setDeregistered
     */
    public function undoDeregistered(RaceSection $section, $laneNumber)
    {
        if (!$this->isDeregistered()) {
            throw new \InvalidArgumentException('Cannot undo a non-de-registered registration!');
        }
        if (is_null($section)) {
            throw new \InvalidArgumentException('No section given!');
        }
        if (is_null($laneNumber) || !is_numeric($laneNumber) || $laneNumber <= 0) {
            throw new \InvalidArgumentException('Given lane number must be positive integer');
        }

        $this->registrationStatus = null;
        $this->lane = $laneNumber;
        $this->section = $section;

        return $this;
    }

    public function setChangedTo(Race $race)
    {
        // remove lane since the team is no longer in the race
        $this->lane = -1;
        return $this->setChangedRace($race, self::CHANGED_TO);
    }

    public function hasChangedToNewRace()
    {
        return (self::CHANGED_TO == $this->changeStatus);
    }

    public function setChangedFrom(Race $race)
    {
        return $this->setChangedRace($race, self::CHANGED_FROM);
    }

    public function isFromOtherRace()
    {
        return (self::CHANGED_FROM == $this->changeStatus);
    }

    private function setChangedRace(Race $race, $status)
    {
        $this->changedRace = $race;
        $this->changeStatus = $status;
        return $this;
    }

    public function getChangedRace()
    {
        if (!$this->hasChangedToNewRace() && !$this->isFromOtherRace()) {
            throw new \InvalidArgumentException('Cannot give race change for a non-changed registration!');
        }
        return $this->changedRace;
    }

    public function setStarted()
    {
        $this->registrationStatus = self::STARTED;

        return $this;
    }

    public function isStarted()
    {
        // is named *is* and not *has* because of naming convention of the other status methods
        return (self::STARTED == $this->registrationStatus);
    }

    public function setFinished()
    {
        $this->registrationStatus = self::FINISHED;

        return $this;
    }

    public function isFinished()
    {
        return (self::FINISHED == $this->registrationStatus);
    }

    public function setAborted()
    {
        $this->registrationStatus = self::ABORTED;

        return $this;
    }

    public function isAborted()
    {
        return (self::ABORTED == $this->registrationStatus);
    }

    public function isDone()
    {
        return $this->isFinished() || $this->isAborted() || ($this->isDeregistered() || $this->isNotAtStart());
    }

    public function isValidForRace()
    {
        return !($this->isDeregistered() || $this->hasChangedToNewRace());
    }

    public function getFinalTime($addRunTimings = false)
    {
        if ($this->isFinished()) {
            $startTime = null;
            $finishTime = null;
            /** @var Timing $timing */
            foreach($this->getTimings() as $timing) {
                if ($timing->getCheckpoint() == Registration::CHECKPOINT_FINISH) {
                    $finishTime = $timing;
                } elseif ($timing->getCheckpoint() == Registration::CHECKPOINT_START) {
                    $startTime = $timing;
                }
            }

            $delta = doubleval($finishTime->getTime()->format('U.u')) - doubleval($startTime->getTime()->format('U.u'));

            $hasRunRace = false;
            if ($addRunTimings) {
                if (!is_null($this->getSection()->getRace()->getRunRace())) {
                    $hasRunRace = true;
                }
            }
            if ($addRunTimings && $hasRunRace) {
                /** @var Registration $runReg */
                $runReg = $this->tryToGetCorrespondingRunRegistration();
                if (is_null($runReg)) {
                    $delta = null;
                } elseif (is_null($runReg->getFinalTime())) {
                    $delta = null;
                } else {
                    $delta = $delta + $runReg->getFinalTime();
                }
            }

            return $delta;
        } else {
            //throw new \InvalidArgumentException("Not finished!");
            return null;
        }
    }

    public function tryToGetCorrespondingRunRegistration()
    {
        $myRace = $this->getSection()->getRace();
        if (Race::TYPE_ROW != $myRace->getRaceType()) {
            return null;
        }

        /** @var Race $runRace */
        $runRace = $myRace->getRunRace();
        if (is_null($runRace)) {
            return null;
        }

        $team = $this->getTeam();

        $result = null;
        /** @var RaceSection $section */
        foreach ($runRace->getSections() as $section) {
            /** @var Registration $registration */
            foreach ($section->getValidRegistrations() as $registration) {
                if ($team->getId() == $registration->getTeam()->getId()) {
                    $result = $registration;
                    break;
                }
            }
            if (!is_null($result)) {
                break;
            }
        }
        return $result;
    }
}

