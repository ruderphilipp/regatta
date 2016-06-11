<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Club;
use AppBundle\Entity\Event;
use AppBundle\Entity\Race;
use AppBundle\Entity\Team;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class RegistrationController extends Controller
{
    /**
     * Show page to modify participation (re-register for different race or de-register from this race)
     *
     * @Route("/event/{event}/race/{race}/change", name="registration_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Event $event, Race $race)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var \AppBundle\Repository\RaceRepository $repo */
        $repo = $em->getRepository('AppBundle:Race');
        $all_races = $repo->findAllByEventForChanges($event, $race);

        $data = array();

        $form = $this->createFormBuilder($data)
            ->add('race', ChoiceType::class, array(
                'label' => 'Rennen',
                'expanded' => false,
                'multiple' => false,
                'choices' => $all_races,
                'choice_value' => function($race) {
                    if (is_null($race)) {
                        return "";
                    }
                    return $race->getId();
                },
            ))
            ->add('group', HiddenType::class)
            ->getForm();

        /** @var \Symfony\Component\Form\FormConfigInterface $c */
        $c = $form->getConfig();
        /** @var \Symfony\Component\Security\CSRF\CsrfTokenManager $tokMgr */
        $tokMgr = $c->getOption("csrf_token_manager");
        /** @var \Symfony\Component\Security\CSRF\CsrfToken $csrf_token */
        $csrf_token = $tokMgr->getToken('form');

        $form->handleRequest($request);
        if (count($form->getErrors(true)) > 0) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            /** @var \Symfony\Component\Form\FormError $err */
            foreach($form->getErrors(true) as $err) {
                if ("Symfony\Component\Validator\ConstraintViolation" == get_class($err->getCause())) {
                    /** @var \Symfony\Component\Validator\ConstraintViolation $cause */
                    $cause = $err->getCause();
                    $logger->warning("Error while evaluating form: ".$err->getMessage().' '.$cause->getPropertyPath().' got: '.$cause->getInvalidValue());
                }
            }
            $this->addFlash(
                'error',
                'Beim Ummelden sind Fehler aufgetreten!'
            );
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            /** @var Race $newRace */
            $newRace = $formData["race"];
            /** @var integer $groupId */
            $groupId = (int)$formData["group"];

            /** @var \AppBundle\Repository\RacingGroupsPerSectionRepository $groupRepo */
            $groupRepo = $em->getRepository('AppBundle:RacingGroupsPerSection');
            /** @var \AppBundle\Entity\RacingGroupsPerSection $group */
            $group = $groupRepo->find($groupId);
            $groupRepo->changeRace($group, $race, $newRace);

            $this->addFlash(
                'notice',
                'Mannschaft umgemeldet!'
            );
        }

        return $this->render('registration/edit.html.twig', array(
            'race' => $race,
            'rr' => $repo,
            'all_races' => $all_races,
            'token' => $csrf_token,
        ));
    }

    /**
     * Mark the given competitor group as being not any longer part of the given race.
     *
     * @Route("/race/{race}/deregister/{team}", name="registration_delete")
     * @Method("POST")
     */
    public function deleteAction(Team $team, Race $race)
    {
        // sanity check
        $races = array();
        /** @var \AppBundle\Entity\RacingGroupsPerSection $section */
        foreach($team->getSections() as $section) {
            array_push($races, $section->getSection()->getRace());
        }
        if (!in_array($race, $races)) {
            $this->addFlash(
                'error',
                'Falsche Inputdaten!'
            );
        } else {
            /** @var \AppBundle\Entity\RacingGroupsPerSection $mySection */
            $mySection = null;
            // find the "lane"
            foreach($team->getSections() as $section) {
                if ($section->getSection()->getRace() == $race) {
                    $mySection = $section;
                }
            }
            if (is_null($mySection)) {
                $this->addFlash(
                    'error',
                    'Falsche Inputdaten! Konnte Startbahn nicht ermitteln...'
                );
            } else {
                // mark the "lane" of the section as cancelled
                $mySection->setDeregistered();
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash(
                    'notice',
                    'Mannschaft abgemeldet!'
                );
            }
        }
        return $this->redirectToRoute('registration_edit', array(
            'event' => $race->getEvent()->getId(),
            'race' => $race->getId()));
    }
}