<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Event;
use AppBundle\Entity\Race;
use AppBundle\Entity\RaceSection;
use Psr\Log\LoggerInterface;

/**
 * RaceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RaceRepository extends \Doctrine\ORM\EntityRepository
{
    public function findAllForEvent($id) {
        return $this->findBy(array('event' => $id));
    }

    public function findAllByEventForChanges($event_id, Race $race)
    {
        $all = $this->findAllForEvent($event_id);

        $checkGender = !$this->isMixedGender($race);

        foreach(array_keys($all) as $key) {
            if ($all[$key]->getId() == $race->getId()) {
                // remove the given race from result set
                unset($all[$key]);
            } elseif ($race->getCompetitorsPerGroup() < $all[$key]->getCompetitorsPerGroup()) {
                // number of max starters per group has to be same or
                // greater so that the moved one fits into
                unset($all[$key]);
            } elseif ($race->getAgeMin() > $all[$key]->getAgeMax()) {
                // competitors allowed to start in older groups but not in younger
                unset($all[$key]);
            } elseif (($race->getAgeMax() * 1.3) < $all[$key]->getAgeMin()) {
                // it does not make sence to let people start in classes with much older ones
                unset($all[$key]);
            } elseif ($checkGender && !$this->isMixedGender($all[$key])) {
                // gender has to match
                if ($race->getGender() != $all[$key]->getGender()) {
                    unset($all[$key]);
                }
            }
        }

        // convert change array key values so that they match the ID in the database
        $result = array();
        /** @var Race $r */
        foreach($all as $r) {
            $result[$r->getId()] = $r;
        }

        return $result;
    }

    private function isMixedGender(Race $race)
    {
        return ('a' == $race->getGender());
    }

    public function getOfficialName(Race $race) {
        $name = '';

        switch($race->getAgeClass()) {
            case 'Kind':
                if ($race->getGender() == 'w') {
                    $name = 'Mädchen';
                } elseif($race->getGender() == 'm') {
                    $name = 'Jungen';
                } else {
                    $name = 'JuM';
                }
                $name .= ' (';
                if ($race->getAgeMin() == $race->getAgeMax()) {
                    $name .= $race->getAgeMin();
                } else {
                    $name .= $race->getAgeMin().' bis '.$race->getAgeMax();
                }
                $name .= ' Jahre)';
                break;
            case 'Junior':
                if ($race->getGender() == 'w') {
                    $name = 'Juniorinnen';
                } else {
                    $name = 'Junioren';
                }

                if ($race->getAgeMin() == 15 && ($race->getAgeMax() == 16 || $race->getAgeMax() == $race->getAgeMin())) {
                    $name .= ' B';
                } elseif ($race->getAgeMin() == 17 && ($race->getAgeMax() == 18 || $race->getAgeMax() == $race->getAgeMin())) {
                    $name .= ' A';
                }
            break;
            case 'Senior':
                if ($race->getGender() == 'w') {
                    $name = 'Frauen';
                } elseif($race->getGender() == 'm') {
                    $name = 'Männer';
                } else {
                    $name = 'Senioren';
                }

                if ($race->getAgeMax() < 23) {
                    $name .= ' B';
                } elseif ($race->getAgeMin() > 22 && $race->getAgeMax() < 27) {
                    $name .= ' A';
                } else {
                    $name .= ' ('.$race->getAgeMin().' bis '.$race->getAgeMax().' Jahre)';
                }
            break;
            case 'Master':
                if ($race->getGender() == 'w') {
                    $name = 'Frauen (Masters)';
                } elseif($race->getGender() == 'm') {
                    $name = 'Männer (Masters)';
                } else {
                    $name = 'Masters';
                }
                $name .= ' ('.$race->getAgeMin().' bis '.$race->getAgeMax().' Jahre)';
            break;
            case 'Offen':
                $name = 'Offen';
                $name .= ' ('.$race->getAgeMin().' bis '.$race->getAgeMax().' Jahre)';
            break;
        }

        if ($race->getCompetitorsPerGroup() > 1) {
            $name .= ' ['.$race->getCompetitorsPerGroup().' Pers.]';
        }

        return $name;
    }

    // FIXME not used
    private function getAgeClassSuffix($min, $max) {
        return "(".$min." - ".$max.")";
    }

    public function getNumberOfRegistrations(Race $race) {
        $result = 0;
        /** @var RaceSection $section */
        foreach($race->getSections() as $section) {
            $result += $section->getRegisteredGroups()->count();
        }
        return $result;
    }

    public function getLastNumberForEvent(Event $event) {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('MAX(r.numberInEvent) maxNum')
            ->from('AppBundle:Race', 'r')
            ->orderBy('r.numberInEvent', 'DESC')
            ->where($qb->expr()->eq('r.event', '?1'))
            ->setParameter(1, $event->getId());
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getNextAvailableSection(Race $race, LoggerInterface $logger = null)
    {
        if ($race->getSections()->isEmpty()) {
            // create initial section
            $this->createSection($race, 1, $logger);
        }

        /** @var RaceSection $last */
        $result = null;
        /** @var RaceSection $last */
        $last = $race->getSections()->last();
        /** @var \AppBundle\Repository\RacingGroupsPerSectionRepository $sectionRepo */
        $sectionRepo = $this->getEntityManager()->getRepository('AppBundle:RacingGroupsPerSection');
        // open a new one if the max number of starters is already assigned)
        if ($race->getMaxStarterPerSection() < $sectionRepo->getNextLaneForSection($last)) {
            // create new section
            $result = $this->createSection($race, $last->getNumber() + 1, $logger);
        } else {
            $result = $last;
        }

        return $result;
    }

    private function createSection(Race $race, $number, LoggerInterface $logger = null)
    {
        $em = $this->getEntityManager();

        $section = new RaceSection();
        $section->setRace($race)
            ->setNumber($number);
        $em->persist($section);
        if (!is_null($logger)) {
            $logger->info("Create section #{$number} for race {$race->getId()}");
        }
        $em->flush();
        $em->refresh($race);
        $em->refresh($section);

        return $section;
    }

    public function createOrUpdate(\AppBundle\DRV_Import\Race $race, Event $event, LoggerInterface $logger)
    {
        /** @var Race $dbItem */
        $dbItem = null;
        $em = $this->getEntityManager();

        $dbItem = $this->findOneBy(array('event' => $event, 'numberInEvent' => $race->number));
        if (null != $dbItem) {
            // TODO update
            // $extraText
            $logger->warning("Implementation missing for updating of races in RaceRepository::createOrUpdate");
        } else {
            // TODO create
            $logger->warning("Implementation missing for creation of new races in RaceRepository::createOrUpdate");
        }

        return $dbItem;
    }
}
