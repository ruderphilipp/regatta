<?php

namespace AppBundle\Controller;

use AppBundle\Entity\CheckInsPerClub;
use AppBundle\Entity\Club;
use AppBundle\Entity\Event;
use AppBundle\Entity\Registration;
use AppBundle\Entity\Team;
use AppBundle\Repository\ClubRepository;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class TokenController extends Controller
{
    /**
     * Find the number of tokens taken by this club in this event.
     *
     * @param Club $club The club to look for.
     * @param Event $event The current event.
     * @return int number of tokens taken by this club.
     */
    public function getNumberOfTokensForClub(Club $club, Event $event)
    {
        $tokens = $this->getAllTokens($club, $event);
        return count($tokens);
    }

    public function getAllTokens(Club $club, Event $event)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var QueryBuilder $qb */
//        $qb = $em->createQueryBuilder();
// //-- creates easily >20 SELECT queries (one per section, one per registration, ...)
//        $query = $qb
//            ->select('t.token')
//            ->distinct()
//            ->from('AppBundle:Team', 't')
//            ->leftJoin('t.registrations', 'r')
//            ->leftJoin('r.section', 's')
//            ->leftJoin('s.race', 'rs')
//            ->where($qb->expr()->not($qb->expr()->isNull('t.token')))
//            ->andWhere('t.club = :club')
//            ->andWhere('rs.event = :event')
//            ->setParameter(':club', $club->getId())
//            ->setParameter(':event', $event->getId())
//            ->getQuery();
//        $teamResult = $query->getResult();
//        return $teamResult;

        $mySqlQuery = "
        SELECT DISTINCT(`t`.`token`) AS `token` FROM `teams` AS `t`
        LEFT JOIN `registrations` AS `r` ON (`t`.`id` = `r`.`team_id`)
        LEFT JOIN `race_section` AS `rs` ON (`r`.`section_id` = `rs`.`id`)
        LEFT JOIN `races` ON (`rs`.`race_id` = `races`.`id`)
        WHERE `t`.`token` IS NOT NULL
            AND `t`.`club_id` = ?
            AND `races`.`event_id` = ?
        ";
        // for better logging output
        $mySqlQuery = trim(str_replace('  ', ' ', $mySqlQuery));

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('token', 'token');
        $query = $em->createNativeQuery($mySqlQuery, $rsm);
        $query->setParameter(1, $club->getId());
        $query->setParameter(2, $event->getId());

        $result = $query->getScalarResult();
        // see <http://stackoverflow.com/questions/11657835#22424012>
        $tokens = array();
        foreach($result as $item) {
            $tokens[] = $item['token'];
        }
        return $tokens;
    }

    /**
     * List all competitors that are checked in.
     *
     * @Route("/event/{event}/checked_in_competitors", name="token_all")
     * @Security("has_role('ROLE_REGISTRATION')")
     */
    public function showAllCheckedInCompetitorsAction(Request $request, Event $event)
    {
        $em = $this->getDoctrine()->getManager();
        $result = array();
        $logger = $this->get('logger');

        $teamRepository = $em->getRepository('AppBundle:Team');
        /** @var ClubRepository $clubRepository */
        $clubRepository = $em->getRepository('AppBundle:Club');
        $clubs = $clubRepository->findAll();
        /** @var Club $club */
        foreach ($clubs as $club) {
            $tokens = $this->getAllTokens($club, $event);
            if (0 < count($tokens)) {
                $name = $club->getName();
                $logger->addInfo('found ' . count($tokens) . ' for ' . $name);
                // first letter
                $key = mb_substr($name, 0, 1, 'utf-8');
                foreach ($tokens as $token) {
                    /** @var Team $team */
                    $team = $teamRepository->findOneBy(array('token' => $token));
                    $logger->addInfo('got for token "' . $token . '" the team ' . $team->getId() . ' with ' . count($team->getRegistrations()) . ' registrations');
                    foreach ($team->getRegistrations() as $registration) {
                        if ($this->isInEvent($event, $registration)) {
                            if (!array_key_exists($key, $result)) {
                                $result[$key] = array();
                            }
                            if (!array_key_exists($name, $result[$key])) {
                                $logger->addInfo('is not in array!');
                                $result[$key][$name] = null;
                            }
                            if (is_null($result[$key][$name])) {
                                $logger->addInfo('is NULL!');
                                $result[$key][$name] = new CheckInsPerClub($club);
                            }
                            $logger->addInfo('adding registration ' . $registration->getId());
                            $result[$key][$name]->addRegistration($registration);
                            $logger->addInfo('now for [' . $key . '][' . $name . ']: ' . $result[$key][$name]);
                        }
                    }
                }
            }
        }

        return $this->render(':token:checked_in.html.twig', array(
            'clubs' => $result,
            'event' => $event,
        ));
    }

    private function isInEvent(Event $event, Registration $registration)
    {
        $regId = $registration->getSection()->getRace()->getEvent()->getId();
        return $regId === $event->getId();
    }
}