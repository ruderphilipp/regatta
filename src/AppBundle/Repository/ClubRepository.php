<?php

namespace AppBundle\Repository;

use AppBundle\AppBundle;
use AppBundle\Entity\Club;
use Psr\Log\LoggerInterface;

class ClubRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Find all competitors that are (still) members of this club.
     *
     * @param Club $club The club that should be inspected.
     * @return array all current club memberships
     */
    public function findAllActiveCompetitors(Club $club) {
        $result = array();
        $now = new \DateTime();
        /** @var \AppBundle\Entity\Membership $membership */
        foreach ($club->getMemberships() as $membership) {
            if ($membership->isValidAt($now)) {
                $result[] = $membership;
            }
        }
        return $result;
    }

    /**
     * Find all competitors of a club that are no longer members.
     *
     * @param Club $club The club that should be inspected.
     * @return array all former club memberships
     */
    public function findAllFormerCompetitors(Club $club) {
        $result = array();
        $now = new \DateTime();
        /** @var \AppBundle\Entity\Membership $membership */
        foreach ($club->getMemberships() as $membership) {
            if (!$membership->isValidAt($now)) {
                $result[] = $membership;
            }
        }
        return $result;
    }

    /**
     * @param \AppBundle\DRV_Import\Club $club the reference for updating
     * @param LoggerInterface $logger for debugging messages
     * @return \AppBundle\Entity\Club the created or updated club entity
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function createOrUpdate(\AppBundle\DRV_Import\Club $club, LoggerInterface $logger)
    {
        /**
         * @var \AppBundle\Entity\Club $dbItem
         */
        $dbItem = null;
        // first try to find by ID
        if ($club->drv_id > 0) {
            $dbItem = $this->findOneByDrvId($club->drv_id);
        }
        // if this does not help, do a fuzzy search by name
        if (null == $dbItem) {
            $logger->debug("Searching club by name [$club->name]");
            $dbItem = $this->createQueryBuilder('c')
                ->where('c.name LIKE :name')
                ->setParameter('name', $club->name)
                ->getQuery()
                ->getOneOrNullResult();
        }

        if (null != $dbItem) {
            $logger->debug("Found club with id [{$dbItem->getId()}] for DRV-ID [{$club->drv_id}]");

            // check and update if necessary
            $updates = false;
            if ($dbItem->getDrvId() != $club->drv_id) {
                $dbItem->setDrvId($club->drv_id);
                $updates = true;
            }
            if ($dbItem->getName() != $club->name) {
                $dbItem->setName($club->name);
                $updates = true;
            }
            if ($dbItem->getShortname() != $club->shortname) {
                $dbItem->setShortname($club->shortname);
                $updates = true;
            }
            if ($dbItem->getAbbreviation() != $club->abbreviation) {
                $dbItem->setAbbreviation($club->abbreviation);
                $updates = true;
            }
            if ($dbItem->getCity() != $club->location) {
                $dbItem->setCity($club->location);
                $updates = true;
            }
            if ($updates) {
                $logger->debug("Updating club with id [{$dbItem->getId()}]");
                $this->getEntityManager()->persist($dbItem);

            }
        } else {
            $logger->debug("Found nothing. Create a new club.");
            // create
            $dbItem = new Club();
            $dbItem->setName($club->name);
            $dbItem->setShortname($club->shortname);
            $dbItem->setAbbreviation($club->abbreviation);
            $dbItem->setDrvId($club->drv_id);
            $dbItem->setCity($club->location);
            $this->getEntityManager()->persist($dbItem);
        }

        return $dbItem;
    }
}
