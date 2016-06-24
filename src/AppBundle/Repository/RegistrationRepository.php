<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Race;
use AppBundle\Entity\RaceSection;
use AppBundle\Entity\Registration;

use AppBundle\Entity\Timing;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

/**
 * Registration
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RegistrationRepository extends \Doctrine\ORM\EntityRepository
{
    public function getNextLaneForSection(RaceSection $raceSection)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('MAX(r.lane) maxNum')
            ->from('AppBundle:Registration', 'r')
            ->where($qb->expr()->eq('r.section', '?1'))
            ->setParameter(1, $raceSection->getId());
        return ((int) $qb->getQuery()->getSingleScalarResult()) + 1;
    }

    public function changeRace(Registration $registration, Race $fromRace, Race $toRace)
    {
        if (is_null($registration)) {
            throw new \InvalidArgumentException('Registration must not be NULL');
        } elseif (is_null($fromRace)) {
            throw new \InvalidArgumentException('Source race must not be NULL');
        } elseif (is_null($toRace)) {
            throw new \InvalidArgumentException('Target race must not be NULL');
        }

        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        /** @var \AppBundle\Repository\RaceRepository $raceRepo */
        $raceRepo = $em->getRepository('AppBundle:Race');

        /** @var RaceSection $section */
        $section = $raceRepo->getNextAvailableSection($toRace);

        // mark current team registration as changed and save the new race id
        $registration->setChangedTo($toRace);
        // create new registration for this team in target race
        $newReg = new Registration();
        $newReg->setSection($section)
            ->setLane($this->getNextLaneForSection($section))
            // the competitor team stays the same
            ->setTeam($registration->getTeam())
            ->setChangedFrom($fromRace);

        $em->persist($registration);
        $em->persist($newReg);
        $em->flush();
    }

    public function setTime(Registration $registration, $timestamp, $checkpoint, LoggerInterface $logger = null)
    {
        if (is_null($checkpoint) || false == trim($checkpoint)) { // PHP evaluates an empty string to false
            throw new \InvalidArgumentException('Checkpoint must not be empty!');
        } elseif (is_null($timestamp) || false == trim($timestamp)) { // PHP evaluates an empty string to false
            throw new \InvalidArgumentException('Time must not be empty!');
        }

        /** @var EntityManager $em */
        $em = $this->getEntityManager();

        $checkpoint = trim($checkpoint);

        $dtime = \DateTime::createFromFormat("U", $timestamp);
        // check validity of the given timestamp
        // if parsing did not work, the method returns false (else object)
        if ($dtime === false || false === $dtime->getTimestamp()) {
            throw new \InvalidArgumentException('Time parameter is not a valid timestamp!');
        }

        // check if the checkpoint does not exist for this registration
        /** @var Timing $t */
        foreach($registration->getTimings() as $t) {
            if ($t->getCheckpoint() == $checkpoint) {
                throw new \InvalidArgumentException('There is already a timing for this checkpoint!');
            }
        }

        // validate if setting a checkpoint makes sense
        if ($checkpoint == Registration::CHECKPOINT_START) {
            if (!$registration->isCheckedIn()) {
                throw new \InvalidArgumentException($registration->getId().': '."Competitors on lane {$registration->getLane()} are not checked in for starting!");
            }
        } else {
            // some other checkpoint after the starting line
            if (!$registration->isStarted()) {
                throw new \InvalidArgumentException('Competitors are not on track!');
            }
        }

        // store time and checkpoint in database
        $timing = new Timing();
        $timing->setRegistration($registration)
            ->setTime($dtime)
            ->setCheckpoint($checkpoint);
        $em->persist($timing);

        if (!is_null($logger)) {
            $logger->debug("stored {$registration->getId()} with timestamp {$timestamp} for checkpoint {$checkpoint}");
        }

        if ($checkpoint == Registration::CHECKPOINT_START) {
            $registration->setStarted();
            $em->persist($registration);
        } elseif ($checkpoint == Registration::CHECKPOINT_FINISH) {
            $registration->setFinished();
            $em->persist($registration);
        }

        $em->flush();
    }
}
