<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Competitor;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;

/**
 * CompetitorRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CompetitorRepository extends \Doctrine\ORM\EntityRepository
{
    public function findAll()
    {
        return $this->findBy(array(), array('lastName' => 'ASC', 'firstName' => 'ASC'));
    }

    public function createOrUpdate(\AppBundle\DRV_Import\Athlete $athlete, LoggerInterface $logger)
    {
        /**
         * @var \AppBundle\Entity\Competitor $dbItem
         */
        $dbItem = null;
        $gotError = false;
        // first try to find by ID (string!)
        if (!empty($athlete->drv_id)) {
            $dbItem = $this->findOneByDrvId($athlete->drv_id);
        }
        // if this does not help, do a fuzzy search
        if (null == $dbItem) {
            $query = $this->createQueryBuilder('c')
                ->where('c.lastName LIKE :lname')
                ->andWhere('c.firstName LIKE :fname')
                ->andWhere('c.yearOfBirth = :yob')
                ->setParameter('lname', $athlete->lastname)
                ->setParameter('fname', $athlete->firstname)
                ->setParameter('yob', $athlete->yearofbirth)
                ->getQuery();
            try {
                $dbItem = $query->getOneOrNullResult();
            } catch (NonUniqueResultException $e) {
                $multi = $query->getResult();
                $logger->warning("got multiple results for competitor: [{$athlete->lastname}, {$athlete->firstname}, {$athlete->yearofbirth}]");
                foreach ($multi as $m) {
                    $logger->warning("  ".$m->getId());
                }
                // TODO try harder to find the correct one
                $gotError = true;
            }
        }

        if (null != $dbItem) {
            $logger->debug("Found competitor with id [{$dbItem->getId()}] for DRV-ID [{$athlete->drv_id}]");

            // check and update if necessary
            $updates = false;
            if ($dbItem->getLastName() != $athlete->lastname) {
                $dbItem->setLastName($athlete->lastname);
                $updates = true;
            }
            if ($dbItem->getFirstName() != $athlete->firstname) {
                $dbItem->setFirstName($athlete->firstname);
                $updates = true;
            }
            if ($dbItem->getYearOfBirth() != $athlete->yearofbirth) {
                $dbItem->setYearOfBirth($athlete->yearofbirth);
                $updates = true;
            }
            if ($athlete->is_female && $dbItem->getGender() != 'w') {
                $dbItem->setGender('w');
            } elseif (!$athlete->is_female && $dbItem->getGender() != 'm') {
                $dbItem->setGender('m');
            }
            if ($dbItem->getDrvId() != $athlete->drv_id) {
                $dbItem->setDrvId($athlete->drv_id);
            }

            if ($updates) {
                $logger->debug("Updating competitor with id [{$dbItem->getId()}]");
                $this->getEntityManager()->persist($dbItem);
            }
        } else {
            if (!$gotError) {
                $logger->debug("Found nothing. Create a new competitor.");
                // create competitor
                $dbItem = new Competitor();
                $dbItem->setLastName($athlete->lastname)
                    ->setFirstName($athlete->firstname)
                    ->setYearOfBirth($athlete->yearofbirth)
                    ->setGender(($athlete->is_female) ? 'w' : 'm')
                    ->setDrvId($athlete->drv_id);

                $this->getEntityManager()->persist($dbItem);
            } else {
                throw new NonUniqueResultException("got multiple results for competitor: [{$athlete->lastname}, {$athlete->firstname}, {$athlete->yearofbirth}]");
            }
        }

        return $dbItem;
    }
}
