<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Registration;
use AppBundle\Repository\RaceRepository;

use AppBundle\Entity\Race;
use AppBundle\Entity\Event;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Race start controller.
 */
class StartController extends Controller
{
    /**
     * Overview for starting a race
     *
     * @Route("/event/{event}/race/{race}/start", name="race_start")
     * @Method("GET")
     */
    public function showAction(Event $event, Race $race)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var RaceRepository $repo */
        $repo = $em->getRepository('AppBundle:Race');

        // check if enough competitors (do not trust GUI logic to not call the method/link)
        if ($repo->getNumberOfRegistrations($race) < $race->getStarterMin()) {
            $this->addFlash(
                'error',
                'Mindestteilnehmeranzahl nicht erreicht!'
            );

            return $this->redirectToRoute('race_show', array('event' => $event->getId(), 'race' => $race->getId()));
        }


        return $this->render('race/start.html.twig', array(
            'race' => $race,
            'race_name' => $repo->getOfficialName($race),
        ));
    }

    /**
     * Check-in a team for this specific race.
     *
     * @Route("/team/{registration}/checkIn", name="race_start_checkin")
     * @Method({"GET", "POST"})
     */
    public function checkInAction(Request $request, Registration $registration)
    {
        if ($registration->isCheckedIn()) {
            $this->addFlash(
                'error',
                'Starter ist bereits eingecheckt!'
            );
            if (!is_null($request->headers->get('referer'))) {
                return $this->redirect($request->headers->get('referer'));
            } else {
                return $this->redirectToRoute('homepage');
            }

        }

        $data = array();
        $form = $this->createFormBuilder($data)
            ->add('token', TextType::class, array(
                'label' => 'Token',
            ))
            ->add('ref', HiddenType::class, array(
                'data' => $request->headers->get('referer'),
            ))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            // $data is a simply array with your form fields
            // like "query" and "category" as defined above.
            $data = $form->getData();
            $registration->setCheckedIn($data["token"]);
            $em = $this->getDoctrine()->getManager();
            $em->persist($registration);
            $em->flush();

            return $this->redirect($data['ref']);
        }

        return $this->render('race/checkin.html.twig', array(
            'group' => $registration,
            'form' => $form->createView(),
        ));
    }

    /**
     * Mark a team (registration) as not at start to be able to get an overview
     * and begin the race start sequence if all other competitors are at start.
     *
     * @Route("/team/{registration}/NotAtStart", name="race_start_nas")
     * @Method("GET")
     */
    public function notAtStartAction(Request $request, Registration $registration)
    {
        $em = $this->getDoctrine()->getManager();
        $registration->setCancelled();
        $em->persist($registration);
        $em->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Reset a team (registration) after marking it as "not at start", so that a new registration is possible.
     *
     * @see notAtStartAction
     *
     * @Route("/team/{registration}/resetNas", name="race_start_reset")
     * @Method("GET")
     */
    public function resetNotAtStart(Request $request, Registration $registration)
    {
        $em = $this->getDoctrine()->getManager();
        $registration->undoCancelled();
        $em->persist($registration);
        $em->flush();

        return $this->redirect($request->headers->get('referer'));
    }
}
