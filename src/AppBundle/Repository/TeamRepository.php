<?php

namespace AppBundle\Repository;

use AppBundle\DRV_Import\Boat;
use AppBundle\Entity\Event;
use AppBundle\Entity\Race;
use AppBundle\Entity\Team;
use AppBundle\Entity\Registration;
use Psr\Log\LoggerInterface;

class TeamRepository extends \Doctrine\ORM\EntityRepository
{
    public function createOrUpdate(Boat $boat, Race $race, LoggerInterface $logger)
    {
        /** @var Team $dbItem */
        $dbItem = null;
        // first try to find by ID
        if (!empty($boat->id)) {
            $dbItem = $this->findOneByDrvId($boat->id);
        }
        // if this does not help, do a fuzzy search
        if (null == $dbItem) {
            // TODO search by club + representative + name + race?
        }

        if (null != $dbItem) {
            // TODO updating
            $logger->warning("Implementation missing for updating teams in TeamRepository::createOrUpdate");
        } else {
            // create new team
            $em = $this->getEntityManager();
            $dbItem = new Team();

            /** @var \AppBundle\Entity\Club $club */
            $club = $this->getEntityManager()->getRepository('AppBundle:Club')->findOneByDrvId($boat->club_id);
            if (null == $club) {
                $message = "Found no club with DRV-ID {$boat->club_id}! No team created for "
                    . "[{$boat->name}, {$boat->id}]";
                $logger->warning($message);
                throw new \Exception($message);
            }
            if ($race->getSections()->isEmpty()) {
                /** @var \AppBundle\Repository\RaceRepository $raceRepo */
                $raceRepo = $this->getEntityManager()->getRepository('AppBundle:Race');
                // create initial section
                $raceRepo->createSection($race, 1, $logger);
            }
            /** @var \AppBundle\Entity\RaceSection $raceSection */
            $raceSection = $race->getSections()->last();
            if (null == $raceSection) {
                $message = "Found no section for race {$race->getId()}! No team created for "
                    . "[{$boat->name}, {$boat->id}]";
                $logger->warning($message);
                throw new \Exception($message);
            }
            // save to DB - bugfix: lane for section is always 1 (because section does not exist yet)
            $em->flush();

            $dbItem->setClub($club)
                ->setDrvId($boat->id)
                ->setName($boat->name)
            ;
            $em->persist($dbItem);

            /** @var \AppBundle\Repository\RegistrationRepository $regRepo */
            $regRepo = $this->getEntityManager()->getRepository('AppBundle:Registration');
            $section = new Registration();
            $section
                ->setSection($raceSection)
                ->setLane($regRepo->getNextLaneForSection($raceSection))
                ->setTeam($dbItem)
            ;
            $em->persist($section);
        }

        return $dbItem;
    }

    public function isTokenExistent($token)
    {
        // TODO use DQL to not load the data but only the count
        // find all entries with the given token
        $result = $this->findBy(array('token' => $token));
        return (count($result) > 0);
    }

    public function getNumberOfCheckedInTeamsForEvent(Event $event)
    {
//        SELECT count(*) AS `c`
//        FROM `teams` AS `t`
//        LEFT JOIN `registrations` AS `regs` ON (`regs`.`team_id` = `t`.`id`)
//        LEFT JOIN `race_section` AS `secs` ON (`secs`.`id` = `regs`.`section_id`)
//        LEFT JOIN `races` ON (`races`.`id` = `secs`.`race_id`)
//        WHERE `t`.`token` IS NOT NULL
//            AND `races`.`event_id` = ?
        $query = $this->createQueryBuilder('t')
            ->select('COUNT(t) AS c')
            ->innerJoin('t.registrations', 'reg')
            ->innerJoin('reg.section', 'section')
            ->innerJoin('section.race', 'race')
            ->where('t.token is not null')
            ->andWhere('race.event = :eId')
            ->setParameter('eId', $event->getId())
            ->getQuery();
        return $query->getSingleScalarResult();
    }
}
