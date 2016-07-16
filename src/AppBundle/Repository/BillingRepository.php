<?php

namespace AppBundle\Repository;


use AppBundle\Entity\Billing;
use AppBundle\Entity\Club;
use AppBundle\Entity\Event;

class BillingRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Check if the total paid is equal to the total of open payments
     *
     * @param Event $event The event to inspect.
     * @param Club $club The club for that the check should be done.
     * @return bool <tt>true</tt> if everything was paid
     */
    public function hasPaidEverything(Event $event, Club $club)
    {
        // search for all billings
        $billings = $this->findBy(array('event' => $event->getId(), 'club' => $club->getId()));
        $totalPaid = 0.0;
        /** @var Billing $billing */
        foreach($billings as $billing) {
            $totalPaid += $billing->getEuro() + ($billing->getCent() / 100.0);
        }
        $toPay = $this->getTotalToPay($event, $club);

        return (0 <= ($toPay - $totalPaid));
    }

    public function getTotalToPay(Event $event, Club $club)
    {
        // TODO implementation missing
        return 0.0;
    }

}