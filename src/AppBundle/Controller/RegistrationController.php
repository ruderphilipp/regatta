<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Competitor;
use AppBundle\Entity\Event;
use AppBundle\Entity\Race;
use AppBundle\Entity\RaceSection;
use AppBundle\Entity\Registration;
use AppBundle\Entity\Team;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class RegistrationController extends Controller
{
    /**
     * Creates a new registration entity.
     *
     * @Route("/event/{event}/race/{race}/new", name="registration_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_REGISTRATION')")
     */
    public function newAction(Request $request, Event $event, Race $race)
    {
        $em = $this->getDoctrine()->getManager();
        $registration = new Registration();

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();

        $now = (new \DateTime('now'))->format('Y');
        $minYear = $now - $race->getAgeMax();
        $maxYear = $now - $race->getAgeMin();

        if (Competitor::GENDER_BOTH == $race->getGender()) { // mixed
            $whereGender = $qb->expr()->neq('p.gender', ':gender');
        } else {
            $whereGender = $qb->expr()->eq('p.gender', ':gender');
        }
        $whereYear = $qb->expr()->between('p.yearOfBirth', $minYear, $maxYear);
        $whereSameRace = $qb->expr()->orX();
        $whereSameRace->add($qb->expr()->neq('s.race', ':raceId'));
        $whereSameRace->add($qb->expr()->isNull('s.race'));

        $where = $qb->expr()->andX();
        $where->add($whereYear);
        $where->add($whereGender);
        $where->add($whereSameRace);

        $query = $qb
            ->select('t')
            ->from('AppBundle:Team', 't')
            ->leftJoin('t.registrations', 'r')
            ->leftJoin('r.section', 's')
            ->join('t.members', 'tp')
            ->join('tp.membership', 'membership')
            ->join('membership.person', 'p')
            ->where($where)
            ->setParameter('gender', $race->getGender())
            ->setParameter(':raceId', $race->getId())
            ->addOrderBy('t.id', 'ASC')
            ->getQuery();
        $teamResult = $query->getResult();

        $alreadyRegistered = array();
        foreach ($race->getSections() as $s) {
                /** @var RaceSection $s */
            foreach ($s->getRegistrations() as $r) {
                /** @var Registration $r */
                $alreadyRegistered[] = $r->getTeam();
            }
        }

        // filter by number of members and show only those with the correct team size
        $teams = array();
        /** @var Team $t */
        foreach ($teamResult as $t) {
            if ($t->getMembers()->count() == $race->getTeamsize()) {
                if (!in_array($t, $alreadyRegistered)) {
                    $teams[] = $t;
                }
            }
        }

        if (0 == count($teams)) {
            $this->addFlash(
                'error',
                'Keine passenden Teams gefunden, die noch hinzugefügt werden könnten!'
            );

            return $this->redirectToRoute('race_show', array('event' => $event->getId(), 'race' => $race->getId()));
        }

        $form = $this->createForm(
            'AppBundle\Form\RegistrationType',
            $registration,
            array(
                'teams' => $teams,
            )
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (is_null($registration->getTeam())) {
                $this->addFlash(
                    'error',
                    'Kein Team angegeben!'
                );

                return $this->redirectToRoute('race_show', array('event' => $event->getId(), 'race' => $race->getId()));
            }
            if (is_null($registration->getSection())) {
                if ($race->getSections()->count() > 0) {
                    $registration->setSection($race->getSections()->last());
                } else {
                    $raceRepo = $em->getRepository('AppBundle:Race');
                    $section = $raceRepo->createSection($race, 1);
                    $registration->setSection($section);
                }
            }
            if (is_null($registration->getLane())) {
                // find highest existing lane
                $highestLane = 0;
                /** @var Registration $r */
                foreach ($registration->getSection()->getRegistrations() as $r) {
                    if ($r->getLane() > $highestLane) {
                        $highestLane = $r->getLane();
                    }
                }
                $registration->setLane(1 + $highestLane);
            }
            $em->persist($registration);
            $em->flush();

            $this->addFlash(
                'notice',
                'Neue Meldung wurde angelegt!'
            );

            return $this->redirectToRoute('race_show', array('event' => $event->getId(), 'race' => $race->getId()));
        }

        return $this->render(
            'registration/new.html.twig',
            array(
                'race' => $race,
                'form' => $form->createView(),
            )
        );
    }

    /**
     * Show page to modify participation (re-register for different race or de-register from this race)
     *
     * @Route("/race/{race}/change", name="registration_edit")
     * @Method("POST")
     * @Security("has_role('ROLE_REGISTRATION')")
     */
    public function editAction(Request $request, Race $race)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var \AppBundle\Repository\RaceRepository $repo */
        $repo = $em->getRepository('AppBundle:Race');
        $all_races = $repo->findAllForEvent($race->getEvent()->getId());

        $form = $this->getEditForm($all_races);
        $csrf_token = $this->getCsrfToken($form);

        $form->handleRequest($request);
        if (count($form->getErrors(true)) > 0) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            $errors = array();
            /** @var \Symfony\Component\Form\FormError $err */
            foreach ($form->getErrors(true) as $err) {
                if ('Symfony\Component\Validator\ConstraintViolation' == get_class($err->getCause())) {
                    /** @var \Symfony\Component\Validator\ConstraintViolation $cause */
                    $cause = $err->getCause();
                    $logger->warning(
                        "Error while evaluating form: ".$err->getMessage().' '.$cause->getPropertyPath(
                        ).' got: '.$cause->getInvalidValue()
                    );
                } elseif (get_class($this) == get_class($err->getCause())) {
                    $errors[] = $err->getMessage();
                }
            }

            $this->addFlash(
                'error',
                'Beim Ummelden sind Fehler aufgetreten!'
            );
            foreach ($errors as $e) {
                $this->addFlash(
                    'error',
                    $e
                );
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            /** @var Race $newRace */
            $newRace = $formData["race"];
            /** @var integer $regId */
            $regId = (int)$formData["registration"];

            if ($newRace == $race) {
                $this->addFlash(
                    'error',
                    'Ummelden in das identische Rennen nicht sinnvoll!'
                );
            } else {
                /** @var \AppBundle\Repository\RegistrationRepository $regRepo */
                $regRepo = $em->getRepository('AppBundle:Registration');
                /** @var \AppBundle\Entity\Registration $registration */
                $registration = $regRepo->find($regId);
                $regRepo->changeRace($registration, $race, $newRace);

                $this->addFlash(
                    'notice',
                    'Mannschaft umgemeldet!'
                );
            }
        }

        return $this->redirectToRoute('race_show', array(
            'event' => $race->getEvent()->getId(),
            'race' => $race->getId()));
    }

    /**
     * @param array[Race] $races all relevant races for this competitor
     * @return Form
     */
    private function getEditForm($races)
    {
        // build a form without an entity
        $data = array();

        $result = $this->createFormBuilder($data)
            ->add('race', ChoiceType::class, array(
                'label' => 'Rennen',
                'expanded' => false,
                'multiple' => false,
                'choices' => $races,
                'choice_value' => function($race) {
                    if (is_null($race)) {
                        return "";
                    }
                    return $race->getId();
                },
            ))
            ->add('registration', HiddenType::class);

        return $result->getForm();
    }

    /**
     * @param Form $form The form to inspect
     * @return string The resulting CSRF token
     */
    private function getCsrfToken(Form $form)
    {
        /** @var \Symfony\Component\Form\FormConfigInterface $c */
        $c = $form->getConfig();
        /** @var \Symfony\Component\Security\CSRF\CsrfTokenManager $tokMgr */
        $tokMgr = $c->getOption("csrf_token_manager");
        /** @var \Symfony\Component\Security\CSRF\CsrfToken $csrf_token */
        $token = $tokMgr->getToken('form');
        return $token->getValue();
    }

    public function getEditContentAction(Race $race, Team $team, Registration $registration)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var \AppBundle\Repository\RaceRepository $repo */
        $repo = $em->getRepository('AppBundle:Race');
        $recommended_races = $repo->findAllByEventForChanges($race);
        $all_races = $repo->findAllForEvent($race->getEvent()->getId());

        $csrf_token = $this->getCsrfToken($this->getEditForm(array_merge($recommended_races, $all_races)));

        return $this->render('race/_section.registration.html.twig', array(
            'registration' => $registration,
            'team' => $team,
            'race' => $race,
            'rr' => $repo,
            'recommended' => $recommended_races,
            'all_races' => $all_races,
            'token' => $csrf_token,
        ));
    }

    public function getUndoContentAction(Registration $registration)
    {
        return $this->render('race/_section.undoCancellation.html.twig', array(
            'team' => $registration->getTeam(),
            'race' => $registration->getSection()->getRace(),
            'sectionNumber' => $registration->getSection()->getNumber(),
            'registrationId' => $registration->getId(),
        ));
    }

    /**
     * Take away the marker of deregistration, so that the team is back in race.
     *
     * @Route("/race/{race}/reregister/{registration}", name="registration_undo_cancellation")
     * @Method("POST")
     * @Security("has_role('ROLE_REGISTRATION')")
     */
    public function undoDeregistationAction(Race $race, Registration $registration)
    {
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->get('logger');
        $logger->debug("RegistrationController:undoDeregistationAction({$race->getId()}, {$registration->getId()})");
        /** @var boolean $sanityCheckOk */
        $sanityCheckOk = $this->allRegistrationsValidForRace(array($registration), $race);
        if ($sanityCheckOk) {
            /** @var RaceSection $mySection */
            $mySection = $registration->getSection();
            // is it still possible to add the team to the old section?
            if (!$mySection->canTakeMoreTeams()) {
                $this->addFlash(
                    'error',
                    sprintf('Abteilung %d wurde bereits gestartet! Kein Hinzufügen mehr möglich...', $mySection->getNumber())
                );
                $mySection = null;
            } else {
                // is there some space?
                try {
                    $this->findNextFreeLaneInSection($mySection);
                } catch (\InvalidArgumentException $e) {
                    $mySection = null;
                }
            }
            // Do I need to look for a new section?
            if (is_null($mySection)) {
                try {
                    $mySection = $this->findNextFreeSection($race);
                } catch (\Exception $e) {
                    $this->addFlash(
                        'error',
                        $e->getMessage()
                    );
                }
            }
            // Was the search for a section successful?
            if (!is_null($mySection)) {
                /** @var int $lane */
                $lane = $this->findNextFreeLaneInSection($mySection);
                $registration->undoDeregistered($mySection, $lane);
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash(
                    'notice',
                    sprintf('Abmeldung zurückgenommen (siehe Abteilung %d)', $mySection->getNumber())
                );
            }
        }
        return $this->redirectToRoute('race_show', array(
            'event' => $race->getEvent()->getId(),
            'race' => $race->getId()));
    }

    /**
     * Get the first section of the given race that is valid to add at least one more team.
     *
     * @param Race $race The race to inspect.
     * @return RaceSection The next valid race section where there is still space for new teams
     * @throws \Exception if there is no free section at all
     */
    protected function findNextFreeSection(Race $race)
    {
        $result = null;
        foreach ($race->getSections() as $section) {
            try {
                $this->findNextFreeLaneInSection($section);
                // this will only be reached if there is a free lane
                $result = $section;
                break; // found one --> leave iteration
            } catch (\InvalidArgumentException $e) {
                // no free lane
            }
        }

        if (is_null($result)) {
            throw new \Exception("Konnte keine einzige freie Abteilung in dem Rennen ermitteln!");
        }

        return $result;
    }

    /**
     * @param RaceSection $section
     * @return int
     * @throws InvalidArgumentException if no free lane exists
     */
    protected function findNextFreeLaneInSection(RaceSection $section)
    {
        if (!$section->canTakeMoreTeams()) {
            throw new \InvalidArgumentException(sprintf('Abteilung %d hat keine freie Bahn mehr...', $section->getNumber()));
        }
        $result = -1;

        // check if there is some space in the middle and if the lane
        // number is smaller than the total number of available lanes
        $max = $section->getRace()->getMaxStarterPerSection();
        for($lane = 1; $lane <= $max; $lane++) {
            // is the lane already in use?
            $inUse = false;
            /** @var Registration $team */
            foreach ($section->getValidRegistrations() as $team) {
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
            throw new \InvalidArgumentException(sprintf('Abteilung %d hat keine freie Bahn mehr...', $section->getNumber()));
        }

        return $result;
    }

    /**
     * Mark the given team as being not any longer part of the given race.
     *
     * @Route("/race/{race}/deregister/{team}", name="registration_delete")
     * @Method("POST")
     * @Security("has_role('ROLE_REGISTRATION')")
     */
    public function deleteAction(Team $team, Race $race)
    {
        $sanityCheckOk = $this->allRegistrationsValidForRace($team->getRegistrations(), $race);
        if ($sanityCheckOk) {
            /** @var \AppBundle\Entity\Registration $myRegistrationForThisRace */
            $myRegistrationForThisRace = null;
            foreach ($team->getRegistrations() as $registration) {
                if ($registration->getSection()->getRace() == $race) {
                    $myRegistrationForThisRace = $registration;
                }
            }
            if (is_null($myRegistrationForThisRace)) {
                $this->addFlash(
                    'error',
                    'Falsche Inputdaten! Konnte Startbahn nicht ermitteln...'
                );
            } else {
                // mark the "lane" of the section as cancelled
                $myRegistrationForThisRace->setDeregistered();
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash(
                    'notice',
                    'Mannschaft abgemeldet!'
                );
            }
        }
        return $this->redirectToRoute('race_show', array(
            'event' => $race->getEvent()->getId(),
            'race' => $race->getId()));
    }

    /**
     * Check if the given registrations belong to the given race.
     *
     * Adds a <em>error</em> flash notice if any of those does not.
     *
     * @param array[Registration] $registrations The registrations to check.
     * @param Race $race The race that might contain the registrations.
     * @return bool <tt>True</tt> if all registrations belong to the given race.
     */
    protected function allRegistrationsValidForRace($registrations, Race $race)
    {
        $result = false;
        // sanity check
        $races = array();
        /** @var \AppBundle\Entity\Registration $registration */
        foreach ($registrations as $registration) {
            array_push($races, $registration->getSection()->getRace());
        }
        if (!in_array($race, $races)) {
            $this->addFlash(
                'error',
                'Falsche Inputdaten!'
            );
        } else {
            $result = true;
        }

        return $result;
    }
}