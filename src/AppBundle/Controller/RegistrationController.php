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

class RegistrationController extends Controller
{
    /**
     * Creates a new Event entity.
     *
     * @Route("/event/{event}/race/{race}/new", name="registration_new")
     * @Method({"GET", "POST"})
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

    /**
     * Mark the given team as being not any longer part of the given race.
     *
     * @Route("/race/{race}/deregister/{team}", name="registration_delete")
     * @Method("POST")
     */
    public function deleteAction(Team $team, Race $race)
    {
        // sanity check
        $races = array();
        /** @var \AppBundle\Entity\Registration $registration */
        foreach ($team->getRegistrations() as $registration) {
            array_push($races, $registration->getSection()->getRace());
        }
        if (!in_array($race, $races)) {
            $this->addFlash(
                'error',
                'Falsche Inputdaten!'
            );
        } else {
            /** @var \AppBundle\Entity\Registration $myRegistrationForThisRace */
            $myRegistrationForThisRace = null;
            // find the "lane"
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
}